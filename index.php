<?php
/* =====================================================
   ACHIDRIVE — site de covoiturage (PHP + MySQL)
   Version améliorée après le projet de cours :
   - identifiants de connexion sortis du code (config.php)
   - requêtes préparées avec paramètres (contre les injections SQL)
   - échappement de tous les affichages (contre les failles XSS)
   - réservation sécurisée (places restantes, pas de doublon)
===================================================== */

session_start(); // pour que la session démarre

// ─── Connexion à la base de données ───
// Les identifiants sont dans config.php (non publié, voir config.example.php)
if (!file_exists(__DIR__ . '/config.php')) {
    die("Fichier config.php manquant : copiez config.example.php en config.php et renseignez vos identifiants.");
}
$config = require __DIR__ . '/config.php';

try {
    $dbh = new PDO($config['dsn'], $config['user'], $config['password'], [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // une erreur SQL lève une exception
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,                  // vraies requêtes préparées côté serveur
    ]);
} catch (PDOException $e) {
    die("Problème de connexion à la base de données.");
}

// Échappe une valeur avant de l'afficher dans la page (protection XSS)
function e($valeur) {
    return htmlspecialchars((string) $valeur, ENT_QUOTES, 'UTF-8');
}

// Date du jour au format AAMMJJ (format imposé par le sujet)
$dateDuJour = date('ymd');

// ─── Déconnexion ───
if (isset($_GET['deconnexion'])) {
    session_destroy(); // les données de la session sont détruites
    header("Location: index.php"); // on revient sur la page d'accueil
    exit;
}

$message = "";       // message affiché à l'utilisateur
$messageType = "";   // 'succes' ou 'erreur'

// ─── Connexion (login) ───
if (isset($_POST['connexion'])) {
    $email = trim($_POST['email'] ?? '');
    $motDePasse = $_POST['motDePasse'] ?? '';

    // Les valeurs saisies sont passées en paramètres : elles ne peuvent plus modifier la requête
    $sth = $dbh->prepare("SELECT email, prenom FROM Internautes WHERE email = ? AND motDePasse = SHA2(?, 224)");
    $sth->execute([$email, $motDePasse]);
    $user = $sth->fetch();

    if ($user) {
        session_regenerate_id(true); // nouvel identifiant de session après connexion
        $_SESSION['user'] = $user['email'];
        $_SESSION['nom'] = $user['prenom'];
    } else {
        $message = "Erreur d'identifiants :/";
        $messageType = "erreur";
    }
}

// ─── Réservation d'un trajet ───
if (isset($_POST['reserver']) && isset($_SESSION['user'])) {
    $idProposition = filter_var($_POST['idProposition'] ?? '', FILTER_VALIDATE_INT);
    $emailPassager = $_SESSION['user'];

    if ($idProposition === false) {
        $message = "Trajet introuvable.";
        $messageType = "erreur";
    } else {
        // Transaction : soit tout est enregistré, soit rien
        $dbh->beginTransaction();
        try {
            $sth = $dbh->prepare("SELECT emailConducteur, nbPlaces FROM Propositions WHERE idProposition = ?");
            $sth->execute([$idProposition]);
            $trajet = $sth->fetch();

            $sth = $dbh->prepare("SELECT COUNT(*) FROM Transports WHERE idProposition = ? AND emailPassager = ?");
            $sth->execute([$idProposition, $emailPassager]);
            $dejaReserve = $sth->fetchColumn() > 0;

            if (!$trajet) {
                $message = "Trajet introuvable.";
            } elseif ($trajet['emailConducteur'] === $emailPassager) {
                $message = "Vous ne pouvez pas réserver votre propre trajet.";
            } elseif ($dejaReserve) {
                $message = "Vous avez déjà réservé ce trajet.";
            } elseif ($trajet['nbPlaces'] <= 0) {
                $message = "Désolé, ce trajet est complet.";
            } else {
                // On retire une place seulement s'il en reste (évite de passer sous zéro)
                $sth = $dbh->prepare("UPDATE Propositions SET nbPlaces = nbPlaces - 1 WHERE idProposition = ? AND nbPlaces > 0");
                $sth->execute([$idProposition]);

                if ($sth->rowCount() === 1) {
                    $sth = $dbh->prepare("INSERT INTO Transports (idProposition, emailPassager) VALUES (?, ?)");
                    $sth->execute([$idProposition, $emailPassager]);
                    $message = "Réservation confirmée !!";
                    $messageType = "succes";
                } else {
                    $message = "Désolé, ce trajet est complet.";
                }
            }

            if ($messageType === "succes") {
                $dbh->commit();
            } else {
                $dbh->rollBack();
                $messageType = "erreur";
            }
        } catch (PDOException $ex) {
            $dbh->rollBack();
            $message = "La réservation a échoué, veuillez réessayer.";
            $messageType = "erreur";
        }
    }
}

// ─── Proposition d'un trajet ───
if (isset($_POST['proposerTrajet']) && isset($_SESSION['user'])) {
    $villeD = trim($_POST['villeD'] ?? '');
    $villeA = trim($_POST['villeA'] ?? '');
    $date   = trim($_POST['date'] ?? '');
    $places = filter_var($_POST['places'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 8]]);
    $prix   = filter_var($_POST['prix'] ?? '', FILTER_VALIDATE_FLOAT);

    if ($villeD === '' || $villeA === '') {
        $message = "Merci d'indiquer une ville de départ et d'arrivée.";
    } elseif (!preg_match('/^\d{6}$/', $date)) {
        $message = "La date doit être au format AAMMJJ (6 chiffres).";
    } elseif ($date < $dateDuJour) {
        // ajout demandé lors de la démo : on refuse une date déjà passée
        $message = "Attention cette date est déjà passée :(";
    } elseif ($places === false) {
        $message = "Le nombre de places doit être compris entre 1 et 8.";
    } elseif ($prix === false || $prix < 0) {
        $message = "Le prix doit être un nombre positif.";
    } else {
        $sth = $dbh->prepare("INSERT INTO Propositions (emailConducteur, villeD, villeA, dateSouhaite, nbPlaces, prix) VALUES (?, ?, ?, ?, ?, ?)");
        $sth->execute([$_SESSION['user'], $villeD, $villeA, $date, $places, $prix]);
        $message = "Votre trajet a été publié avec succès !!";
        $messageType = "succes";
    }
    if ($messageType !== "succes") {
        $messageType = "erreur";
    }
}

// ─── Recherche des trajets disponibles ───
$resultats = [];
if (isset($_SESSION['user'])) {
    // trajets à venir où il reste de la place
    $conditions = ["nbPlaces > 0", "dateSouhaite >= ?"];
    $params = [$dateDuJour];

    if (isset($_POST['rechercheSubmit'])) {
        if (!empty($_POST['villeD'])) {
            $conditions[] = "villeD = ?";
            $params[] = trim($_POST['villeD']);
        }
        if (!empty($_POST['villeA'])) {
            $conditions[] = "villeA = ?";
            $params[] = trim($_POST['villeA']);
        }
        if (!empty($_POST['date'])) {
            $conditions[] = "dateSouhaite = ?";
            $params[] = trim($_POST['date']);
        }
        if (isset($_POST['prix']) && $_POST['prix'] !== '' && is_numeric($_POST['prix'])) {
            $conditions[] = "prix <= ?";
            $params[] = (float) $_POST['prix'];
        }
    }

    // Seuls des morceaux de requête écrits par nous sont assemblés ; les valeurs restent des paramètres
    $sql = "SELECT * FROM Propositions WHERE " . implode(" AND ", $conditions) . " ORDER BY dateSouhaite";
    $sth = $dbh->prepare($sql);
    $sth->execute($params);
    $resultats = $sth->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>ACHIDRIVE</title>
        <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
        <!-- bibliothèque d'icônes -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
        <link rel="stylesheet" href="style.css" />
    </head>
<body>
   <header>
       <div class="bandeau">
           <h1 class="nomEnseigne"><i class="fas fa-car"></i>CHIDRIVE</h1>
       </div>

       <?php if (isset($_SESSION['user'])) { ?>
           <p>
               Bonjour <strong><?php echo e($_SESSION['nom']); ?></strong> !
               <a href="?deconnexion=1" class="lienDeconnexion">Se déconnecter</a>
           </p>
       <?php } else { ?>
           <p>Le best site de covoiturage in the world</p>
       <?php } ?>
   </header>

   <main>
       <?php if ($message !== "") { ?>
           <p class="message message-<?php echo e($messageType); ?>"><?php echo e($message); ?></p>
       <?php } ?>

       <?php
       // si l'utilisateur n'est pas connecté, on affiche le formulaire de connexion
       if (!isset($_SESSION['user'])) {
       ?>
           <section class="sectionRecherche conteneurConnexion">
               <h2 style="text-align:center; color:#63A361;">Connexion</h2>

               <form method="POST" action="">
                   <div class="inputGroup" style="margin-bottom: 15px;">
                       <i class="fas fa-envelope"></i>
                       <input type="email" name="email" placeholder="Email" required>
                   </div>

                   <div class="inputGroup" style="margin-bottom: 15px;">
                       <i class="fas fa-lock"></i>
                       <input type="password" name="motDePasse" placeholder="Mot de passe" required>
                   </div>

                   <button type="submit" name="connexion" style="width:100%">SE CONNECTER</button>
               </form>
           </section>
       <?php
       } else { // sinon on affiche l'interface du site
       ?>
           <section class="sectionRecherche">
               <form class="barreRecherche" action="" method="POST">
                   <div class="inputGroup">
                        <i class="fas fa-map-marker-alt"></i>
                        <input type="text" name="villeD" placeholder="Départ">
                   </div>
                   <div class="inputGroup">
                       <i class="fas fa-map-pin"></i>
                       <input type="text" id="barreArrivee" name="villeA" placeholder="Arrivée">
                   </div>
                   <div class="inputGroup">
                       <i class="fas fa-calendar-alt"></i>
                       <input type="text" name="date" placeholder="Date (AAMMJJ)" pattern="\d{6}">
                    </div>
                    <div class="inputGroup">
                       <i class="fas fa-euro-sign"></i>
                       <input type="number" name="prix" step="0.01" min="0" placeholder="Max €">
                    </div>
                   <button type="submit" name="rechercheSubmit">RECHERCHER</button>
               </form>
           </section>

           <article class="destinations">
               <h2>Destinations du moment</h2>
               <div class="destinationsGrid">

                   <div class="destinationCarre">
                       <img src="https://images.unsplash.com/photo-1502602898657-3e91760cbb34?q=80&w=2073&auto=format&fit=crop" alt="Paris" class="dest-img">
                       <div class="infoDestination">
                           <h3>Paris</h3>
                           <p class="price">dès 15€</p>
                           <button type="button" class="boutonVoir" onclick="choisirDestination('Paris')">Voir</button>
                       </div>
                   </div>

                   <div class="destinationCarre">
                       <img src="https://plus.unsplash.com/premium_photo-1661957705604-15f37be44856?q=80&w=2273&auto=format&fit=crop" alt="Lyon" class="dest-img">
                       <div class="infoDestination">
                           <h3>Lyon</h3>
                           <p class="price">dès 8€</p>
                           <button type="button" class="boutonVoir" onclick="choisirDestination('Lyon')">Voir</button>
                       </div>
                   </div>

                   <div class="destinationCarre">
                       <img src="https://images.unsplash.com/photo-1566838217578-1903568a76d9?q=80&w=2070&auto=format&fit=crop" alt="Marseille" class="dest-img">
                       <div class="infoDestination">
                           <h3>Marseille</h3>
                           <p class="price">dès 12€</p>
                           <button type="button" class="boutonVoir" onclick="choisirDestination('Marseille')">Voir</button>
                       </div>
                   </div>
               </div>
           </article>

           <article>
               <h2>Trajets disponibles</h2>
               <?php if (count($resultats) === 0) { ?>
                   <p style="text-align:center">Aucun trajet ne correspond à votre recherche pour le moment :/</p>
               <?php } else { ?>
                   <table class="tableau">
                       <tr>
                           <th>Conducteur</th>
                           <th>Départ</th>
                           <th>Arrivée</th>
                           <th>Date</th>
                           <th>Places</th>
                           <th>Prix</th>
                           <th>Action</th>
                       </tr>
                       <?php foreach ($resultats as $enr) { ?>
                           <tr>
                               <td><?php echo e($enr['emailConducteur']); ?></td>
                               <td><?php echo e($enr['villeD']); ?></td>
                               <td><?php echo e($enr['villeA']); ?></td>
                               <td><?php echo e($enr['dateSouhaite']); ?></td>
                               <td><?php echo e($enr['nbPlaces']); ?></td>
                               <td><?php echo e($enr['prix']); ?> €</td>
                               <td>
                                   <form method="POST">
                                       <input type="hidden" name="idProposition" value="<?php echo e($enr['idProposition']); ?>">
                                       <button type="submit" name="reserver" class="boutonReserver">Réserver</button>
                                   </form>
                               </td>
                           </tr>
                       <?php } ?>
                   </table>
               <?php } ?>
           </article>

           <section class="sectionRecherche sectionPublication">
                <h3>Conducteur ? Publiez une annonce</h3>
                <form class="barreRecherche" action="" method="POST" style="justify-content: center;">
                   <div class="inputGroup"><input type="text" name="villeD" placeholder="Départ" required></div>
                   <div class="inputGroup"><input type="text" name="villeA" placeholder="Arrivée" required></div>
                   <div class="inputGroup"><input type="text" name="date" placeholder="AAMMJJ" required pattern="\d{6}"></div>
                   <div class="inputGroup"><input type="number" name="places" placeholder="Places" min="1" max="8" required style="width:60px"></div>
                   <div class="inputGroup"><input type="number" step="0.01" min="0" name="prix" placeholder="Prix €" required style="width:60px"></div>
                   <button type="submit" name="proposerTrajet" class="boutonVoir">PUBLIER</button>
               </form>
           </section>

           <article class="commentaires">
               <h2>Ils nous font confiance</h2>
               <div class="carreCommentaires">
                   <div class="carreComm">
                       <img src="https://images.unsplash.com/photo-1633068587634-4280dabf12ed?q=80&w=1007&auto=format&fit=crop" alt="Terrie" class="avatar-photo">
                       <div class="contenuComm">
                           <p>"Super ambiance et très ponctuel !"</p>
                           <small><span class="etoiles">★★★★★</span> Terrie</small>
                       </div>
                   </div>

                   <div class="carreComm">
                       <img src="https://images.unsplash.com/photo-1639747280804-dd2d6b3d88ac?q=80&w=987&auto=format&fit=crop" alt="Abdel" class="avatar-photo">
                       <div class="contenuComm">
                           <p>"Très économique et pratique, que demander de plus."</p>
                           <small><span class="etoiles">★★★★☆</span> Abdel</small>
                       </div>
                   </div>

                   <div class="carreComm">
                       <img src="https://images.unsplash.com/photo-1619895862022-09114b41f16f?q=80&w=2070&auto=format&fit=crop" alt="Julie" class="avatar-photo">
                       <div class="contenuComm">
                           <p>"Conducteur hyper sympathique ^^"</p>
                           <small><span class="etoiles">★★★★★</span> Julie</small>
                       </div>
                   </div>
               </div>
           </article>
       <?php } ?>
   </main>

   <footer>
       <p>&copy; 2025 - Projet Web Akidat &amp; Chirine</p>
   </footer>

   <script>
       // Un clic sur "Voir" remplit le champ Arrivée et remonte en haut de la page
       function choisirDestination(ville) {
           const champArrivee = document.getElementById('barreArrivee');
           if (champArrivee) {
               champArrivee.value = ville;
               window.scrollTo({ top: 0, behavior: 'smooth' });
           }
       }
   </script>
</body>
</html>
