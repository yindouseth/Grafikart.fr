# TODO — Mentoring

Liste de travail issue des ADR du domaine. Les cases cochées correspondent aux éléments livrés sur la branche courante (`bed70c6d`, `96d08964` et `277735a4`).

## 1. Consolider le modèle métier

- [x] Créer les modèles initiaux `MentoringAvailability`, `MentoringException` et `MentoringBooking`, ainsi que les tables associées.
- [x] Ajouter une relation polymorphe nullable `transactions.transactionable`, compatible avec les transactions Premium existantes.
- [ ] Compléter la migration `mentoring_bookings` : instantanés de prix (6 000 centimes TTC) et durée (60 minutes), fuseau du participant, identifiant Checkout Stripe, secret de salle kMeet, UID et séquence iCalendar, dates métier et métadonnées nécessaires.
- [ ] Ajouter `mentoring_booking_schedule_histories` avec ancien/nouveau créneau, auteur, cause et date du changement.
- [ ] Définir les états `PendingPayment`, `Scheduled`, `NeedsRescheduling`, `Completed` et `Cancelled` (enum/casts, transitions et règles d’accès).
- [ ] Garantir les invariants en base et dans le domaine : cohérence état/créneau, unicité des identifiants Stripe, transaction de mentoring non remboursée pour une réservation confirmée.
- [ ] Prévoir un mécanisme transactionnel empêchant tout chevauchement entre deux immobilisations ou réservations actives, tampon de 15 minutes inclus.
- [ ] Adapter les factories et jeux de données de test aux réservations et à leurs états.

## 2. Moteur de disponibilités

- [ ] Créer un service pur de calcul de créneaux, testable indépendamment de HTTP et de Stripe.
- [ ] Appliquer la priorité des exceptions datées sur la semaine type, y compris l’indisponibilité complète et les plages multiples.
- [ ] Gérer les règles en `Europe/Paris`, les changements d’heure et le stockage des occurrences en UTC.
- [ ] Générer les débuts au quart d’heure pour des sessions de 60 minutes entièrement contenues dans une plage.
- [ ] Exclure les créneaux à moins de 24 h, au-delà de 40 jours, ou en conflit avec une réservation et son tampon.
- [ ] Rejouer cette validation sous verrou transactionnel au moment de l’immobilisation.
- [ ] Détecter et signaler dans le CMS les modifications de disponibilité qui entrent en conflit avec une réservation confirmée, sans la déplacer.
- [ ] Ajouter les tests de frontières : plages multiples, exceptions, DST, préavis, horizon et chevauchements partiels.

## 3. Parcours participant

- [ ] Ajouter les routes et l’autorisation : consultation des créneaux et réservation réservées aux utilisateurs authentifiés.
- [ ] Réaliser le calendrier mensuel limité aux 40 jours, avec dates sans créneau désactivées et raccourci vers le prochain créneau disponible.
- [ ] Afficher les heures dans le fuseau détecté, permettre son changement et afficher clairement le fuseau utilisé.
- [ ] Ajouter le formulaire de préparation : sujet/objectif obligatoire et description Markdown facultative avec l’éditeur et la sanitisation existants.
- [ ] Créer l’action « Payer 60 € » : revalidation atomique, immobilisation temporaire et retour explicite au sélecteur si le créneau vient d’être pris.
- [ ] Créer l’espace participant : listes active/à replanifier/passée et page de détail de chaque réservation.
- [ ] Permettre la modification du sujet et de la description jusqu’à H−24, avec notification interne du mentor après paiement.
- [ ] Permettre un unique report autonome jusqu’à H−24 ; conserver le compteur et les informations de préparation.
- [ ] Afficher le bouton kMeet uniquement de H−10 à fin +15 min, après contrôle de l’identité du participant.

## 4. Paiement Stripe et webhooks

- [ ] Créer la session Stripe Checkout avec carte, total TTC fixe de 6 000 centimes, fiscalité existante et expiration synchronisée à 30 minutes.
- [ ] Ajouter `purchase_type=mentoring` et `booking_id` aux métadonnées de la Checkout Session et du PaymentIntent.
- [ ] Rendre cohérente la création de l’immobilisation et de Checkout ; libérer le créneau si la création Stripe échoue.
- [ ] Router les `PaymentEvent` par métadonnées, sans réutiliser la recherche Premium par montant.
- [ ] Confirmer la réservation exclusivement depuis un webhook Stripe authentifié, de manière idempotente, et créer/relier la transaction.
- [ ] Gérer les expirations et échecs Checkout : supprimer la réservation temporaire et libérer le créneau, même sans retour navigateur.
- [ ] Traiter les remboursements Stripe ou administratifs : marquer la transaction, annuler définitivement la réservation et éviter les doubles effets.
- [ ] Couvrir les courses critiques : deux checkouts concurrents, webhook dupliqué et webhook reçu après expiration.

## 5. CMS

- [x] Ajouter au CMS la gestion de la semaine type : plusieurs plages indépendantes par jour, ajout, retrait et sauvegarde transactionnelle.
- [x] Ajouter au CMS la gestion des exceptions datées : plages spécifiques, indisponibilité complète, sélection de plusieurs dates et suppression d'une exception.
- [x] Valider les horaires saisis sur une grille de 15 minutes et l'ordre début/fin ; refuser les chevauchements de plages d'exception.
- [x] Couvrir les interfaces CMS de disponibilités et d'exceptions par des tests fonctionnels.
- [ ] Valider également l'absence de chevauchement entre les plages récurrentes lors de leur mise à jour.
- [ ] Ajouter la liste filtrable des réservations à venir, à replanifier et passées.
- [ ] Ajouter le détail CMS : participant, contenu Markdown rendu de manière sûre, historique des créneaux et transaction.
- [ ] Permettre au mentor d’annuler un créneau, libérer l’ancien créneau et passer la réservation à replanifier sans consommer le report participant.
- [ ] Permettre au mentor de remettre exceptionnellement une réservation réalisée à replanifier, avec traçabilité.
- [ ] Ne pas inclure de sélection manuelle de créneau, de régénération kMeet ni de remboursement dans le CMS MVP.

## 6. Communications et calendrier

- [ ] Générer un secret de salle kMeet cryptographiquement aléatoire lors de la confirmation, et construire l’URL sans appel API kMeet.
- [ ] Notifier le mentor (notification interne + e-mail) après une confirmation ou un report, avec participant, horaire Paris, sujet et lien CMS.
- [ ] Notifier le participant après confirmation, report, annulation par le mentor ou remboursement, dans son fuseau choisi.
- [ ] Générer les invitations iCalendar : UID stable, séquence incrémentée, horaire/fuseau/sujet et lien vers la page authentifiée (jamais l’URL kMeet).
- [ ] Envoyer les mises à jour et annulations iCalendar appropriées à chaque report, annulation ou remboursement.
- [ ] Planifier les rappels H−24 et H−1 ; invalider ceux de l’ancien créneau après report et ne jamais proposer une action expirée.
- [ ] Mettre les e-mails et notifications en file après validation de la transaction en base.

## 7. Automatisations et cycle de vie

- [ ] Planifier une tâche idempotente d’expiration des immobilisations et synchronisation avec Checkout Stripe.
- [ ] Planifier une tâche idempotente qui clôt les réservations planifiées à la fin du créneau (`Completed`).
- [ ] Planifier l’envoi/programmage idempotent des rappels.
- [ ] Anonymiser sujet et description un an après la réalisation, sans toucher aux données transactionnelles.
- [ ] Bloquer la suppression autonome d’un compte qui possède une réservation temporaire, future ou à replanifier.
- [ ] Vérifier qu’une réservation réalisée ou annulée ne bloque pas la suppression du compte.

## 8. Recette de bout en bout

- [ ] Vérifier le parcours complet : sélection → Checkout → webhook → confirmation → notifications → invitation calendrier.
- [ ] Vérifier les reports participant et mentor, y compris les droits, l’historique, les rappels et les invitations existantes.
- [ ] Vérifier le remboursement et les effets associés : libération, annulation définitive et notifications idempotentes.
- [ ] Vérifier l’accès kMeet : ni avant H−10, ni après fin +15 min, ni par un autre utilisateur.
- [ ] Vérifier la non-régression Premium après la généralisation des transactions et du routage Stripe.
