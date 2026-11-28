# Utiliser IOMAD Certificate Regenerator

La RC4 permet de corriger un certificat déjà émis après avoir changé le modèle ou les paramètres de l’activité IOMAD Certificate. Elle fournit aussi un inventaire des réglages des certificats dans les cours. C’est un plugin administratif indépendant, réservé aux installations IOMAD qui possèdent ses composants obligatoires. La publication au Marketplace reste soumise à l’évaluation de Moodle.

## Corriger un mauvais modèle ou une date oubliée

1. Dans le cours, ouvrir les paramètres de l’activité **IOMAD Certificate**.
2. Choisir le bon modèle. Pour ajouter la date d’émission, régler **Print Date → Date Issued** et **Date Format → 1 January 2000**. Le nom des mois suit la langue utilisée pour générer le PDF.
3. Enregistrer les paramètres, puis ouvrir **IOMAD Certificate Regenerator** dans le menu de l’activité.
4. Repérer l’étudiant et vérifier son code et sa date d’émission originale. Un certificat déjà émis avec un PDF natif enregistré est nécessaire.
5. Choisir **Prepare corrected copy**, lire le récapitulatif et approuver la préparation.
6. Ouvrir le PDF. Vérifier le nom, le modèle, la date réellement imprimée et le texte. Si le résultat ne convient pas, corriger les paramètres et préparer un nouvel aperçu.
7. Choisir **Confirm corrected copy** pour conserver exactement le PDF vérifié.
8. Choisir ensuite **Publish corrected certificate for the learner**. Vérifier les fichiers proposés, le motif et la conservation administrative, puis publier.

La préparation et la confirmation seules ne changent pas le certificat de l’étudiant. La publication remplace les fichiers natifs précisément identifiés et fait servir la correction par le bouton de l’activité. Le code et la date d’émission enregistrés restent ceux du certificat initial; la correction possède sa propre trace. Les notes, inscriptions et achèvements ne sont pas modifiés.

Un modèle personnalisé doit réellement prévoir l’impression de la date. Le plugin utilise le fichier PHP installé, mais ne réécrit pas sa mise en page et ne peut pas lui imposer des paramètres qu’il ignore. Faire un essai avec des données fictives pour chaque modèle propre au site.

## Corriger plusieurs étudiants

Choisir **Correct several learners**, sélectionner au plus **30 certificats déjà émis**, puis préparer la sélection. Ouvrir chaque PDF avant de confirmer les copies vérifiées. La publication collective constitue une étape distincte, avec une liste de cibles et un résultat par étudiant. Répéter avec une autre sélection pour un groupe plus grand.

Une interruption ne signifie pas que toute la sélection a réussi. Revenir au lot et lire les résultats. Ne pas modifier simultanément les paramètres de l’activité pendant le traitement.

## Conserver une trace et gérer les anciens PDF

Dans **Administration du site → Plugins → Plugins locaux → IOMAD Certificate Regenerator → Retention settings**, l’administrateur fixe la politique : laisser choisir l’opérateur, toujours conserver ou ne jamais conserver les anciens PDF lors des prochaines publications.

Lorsqu’elle est autorisée, l’archive administrative conserve les fichiers remplacés dans une zone protégée, accessible aux opérateurs autorisés et non par le téléchargement ordinaire de l’étudiant. Le motif et la trace de correction demeurent enregistrés même si aucun ancien PDF n’est conservé. Une nouvelle politique ne supprime pas les archives antérieures. Les règles de durée, de confidentialité et de suppression restent applicables.

La correction ne rappelle pas un fichier déjà téléchargé ou envoyé par courriel. L’archive ne constitue pas une garantie de conservation légale ni un bouton de retour arrière.

## Renvoyer le certificat corrigé

Depuis la copie confirmée, choisir **Email the corrected certificate**. Vérifier le destinataire affiché et approuver l’envoi. Le PDF joint est exactement celui confirmé. Le plugin ne choisit pas une adresse arbitraire et ne renvoie rien automatiquement lors de la préparation, de la publication ou de la consultation.

Le sujet et le message se personnalisent dans **Administration du site → Langue → Personnalisation de la langue → local_iomadcertregen.php**, chaînes `emailsubject` et `emailbody`. Conserver le paramètre `{$a}` du nom du cours. Il s’agit de messages communs à la langue, pas d’un éditeur par envoi. Un envoi accepté par Moodle ne prouve pas sa réception en boîte de courriel.

## Choisir les rôles autorisés

Dans **Administration du site → Plugins → Plugins locaux → IOMAD Certificate Regenerator → Access and permissions**, l’administrateur sélectionne les rôles dans le champ multiple natif Moodle, vérifie la sélection et l’enregistre.

Le rôle Gestionnaire reçoit la capacité du plugin par défaut. Pour un rôle d’administrateur d’entreprise IOMAD, sélectionner le rôle réellement utilisé sur le site si cette délégation est souhaitée. Son nom ne suffit pas : l’utilisateur doit aussi disposer de l’accès au cours et de `mod/iomadcertificate:manage`, ainsi que de `local/iomadcertregen:regenerate`, dans le contexte de l’activité. Le sélecteur n’inscrit personne et ne donne pas les autres permissions Moodle. Les restrictions de groupe et les interdictions continuent de s’appliquer.

L’association du cours à une entreprise IOMAD n’est pas requise pour ce parcours depuis l’activité. Les règles d’accès à l’historique d’entreprise restent contrôlées séparément. Les rapports globaux et les réglages institutionnels sont réservés aux administrateurs du site.

## Créer un rapport utile avec les outils Moodle

Ouvrir **Administration du site → Rapports → Générateur de rapports → Rapports personnalisés** et créer un rapport avec la source **IOMAD Certificate inventory**.

Pour vérifier le modèle, la date et le courriel dans les cours, sélectionner six colonnes :

| Colonne | Vérification |
| --- | --- |
| Cours | Identifier le cours |
| Activité | Distinguer plusieurs certificats dans un cours |
| Identifiant du modèle actuel | Repérer le modèle sélectionné |
| Source de la date imprimée | Attendre Date d’émission si c’est la règle souhaitée |
| Format de la date imprimée | Attendre Jour mois année pour le format 1 janvier 2000 |
| Mode de remise natif | Attendre Courriel si c’est la remise souhaitée |

Ajouter des filtres selon le besoin. Le menu natif de téléchargement sous le tableau propose les formats activés dans Moodle. Le rapport comprend les cours masqués et sans entreprise associée, avec une ligne par activité. Les 36 colonnes disponibles ne sont pas obligatoires dans chaque rapport.

Le rapport décrit les paramètres actuels : il ne vérifie pas le contenu d’anciens PDF et ne prouve pas la livraison d’un courriel. Un modèle peut ignorer un réglage. La remise native par courriel n’automatise pas le renvoi des corrections. Voir le [guide détaillé de l’inventaire](inventory-fr.md).

## Installer ou mettre à jour

Sauvegarder le site complet et commencer en UAT. Utiliser le ZIP joint à la [release RC4](https://github.com/MichelCardinal/moodle-local_iomadcertregen/releases/tag/v1.0.0-rc4), puis l’installateur natif Moodle. Les dépendances `local_iomad` et `mod_iomadcertificate` doivent être présentes. La RC4 ne fonctionne pas sur Moodle seul.

Après une mise à jour depuis RC3 ou une ancienne version de développement, préparer un nouvel aperçu pour les corrections encore en attente. Les PDF déjà publiés et les archives conservées ne sont pas réécrits par la mise à jour. Vérifier un remplacement de modèle, une correction de date, les droits et les téléchargements avant de décider d’une mise en production.

La RC4 reste une candidate. Consulter [TESTING.md](../TESTING.md) pour les révisions IOMAD et PHP exactes, la limite des profils PHP8.4 et les essais différés. Les traductions françaises et espagnoles sont des brouillons locaux distincts de l’anglais inclus dans le ZIP; aucune disponibilité AMOS n’est garantie.
