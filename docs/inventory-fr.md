# Inventaire détaillé des paramètres IOMAD Certificate

Cette fonction décrit les paramètres **actuellement enregistrés dans chaque activité**, avec une ligne par activité IOMAD Certificate. Un cours contenant deux activités apparaît donc sur deux lignes. Les activités sans certificat déjà émis, les cours masqués et les cours sans entreprise IOMAD associée sont inclus. Les activités en cours de suppression sont exclues.

## Créer ou compléter un rapport personnalisé

1. Se connecter comme administrateur du site.
2. Ouvrir **Administration du site → Rapports → Générateur de rapports → Rapports personnalisés**.
3. Créer un rapport avec la source **IOMAD Certificate inventory**, ou ouvrir un rapport existant utilisant cette source.
4. Ajouter les colonnes nécessaires depuis l’entité d’inventaire. Les 36 colonnes sont disponibles; les rapports existants conservent leurs colonnes.
5. Ajouter les filtres ou conditions utiles, puis prévisualiser le résultat.
6. Télécharger le rapport avec les outils d’export natifs de Moodle. Ne diffuser que les informations nécessaires.

Les nouvelles traductions françaises et espagnoles sont préparées localement. Leur disponibilité dans AMOS dépend de la publication du plugin; les noms de colonnes peuvent donc apparaître en anglais selon l’installation.

## Informations disponibles

| Domaine | Colonnes |
| --- | --- |
| Repérage | Catégorie, cours, nom abrégé, nom de l’activité, identifiant du modèle, visibilité du cours et de l’activité |
| Identifiants | Identifiant du cours, du module de cours et de l’instance de certificat |
| Date | Imprimer une date (oui/non), source de la date, format de la date |
| Note | Source de la note, format de la note, élément de note de l’objectif imprimé |
| Texte | Description enregistrée, code de son format, texte personnalisé, heures de formation configurées, impression du code de certificat et des noms des enseignants |
| Présentation | Orientation, image et couleur de bordure, filigrane, signature et sceau |
| Remise et notifications | Affichage dans le navigateur, téléchargement ou courriel; enregistrement des certificats; notification aux enseignants; autres destinataires; inclusion dans l’ancien rapport natif |
| Durée et suivi | Temps minimal requis dans le cours (minutes), date de création et date de dernière modification de l’activité |

## Exemple : vérifier les dates et le modèle dans tous les cours

Ajouter les colonnes **Cours**, **Activité**, **Identifiant du modèle**, **Imprimer une date**, **Source de la date imprimée** et **Format de la date imprimée**.

La source distingue aucune date, la date d’émission, la date d’achèvement du cours et une activité notée. Lorsqu’une activité ou une catégorie de notes est choisie, le rapport indique son nom et son identifiant. Une référence supprimée ou ne correspondant pas au cours est signalée comme introuvable.

Les formats sont décrits séparément : mois/jour/année, mois/jour ordinal/année, jour/mois/année, mois/année ou format de la langue de l’utilisateur. Le suffixe ordinal utilisé par le mode natif est anglais. Les noms de mois et le format de langue dépendent de la langue du rendu du PDF.

Pour retrouver les cours sans date, ajouter le filtre **Imprimer une date = Non**. Pour isoler un format, utiliser le filtre de format. Pour une source d’activité ou de catégorie particulière, les filtres de référence utilisent l’identifiant numérique enregistré.

Le rapport intégré du plugin reste plus compact : il présente les colonnes de repérage, la source et le format de la date, le mode de remise et l’enregistrement du certificat. Pour une analyse complète, utiliser les rapports personnalisés.

## Interpréter correctement les résultats

- Le périmètre couvre les paramètres propres à IOMAD Certificate. Les règles Moodle communes d’achèvement, de restriction d’accès et de groupes ne sont pas détaillées ici.
- Le rapport ne vérifie pas ce qui est imprimé dans un ancien PDF et ne modifie ni l’activité ni les certificats.
- Une option enregistrée peut être ignorée par un modèle personnalisé. Si la date n’est pas activée, un format enregistré ne signifie pas qu’une date sera imprimée.
- Le choix « date d’achèvement du cours » ne prouve pas que le cours est achevé. Selon les données disponibles, le rendu natif peut utiliser la date d’émission.
- Les images sont représentées par leur identifiant de fichier; le rapport n’affirme pas qu’elles existent encore et ne les affiche pas.
- Les formats inconnus ou non configurés sont signalés explicitement, avec leur valeur enregistrée.
- Les textes enregistrés sont affichés comme du texte : leur HTML et leurs scripts ne sont pas exécutés.
- Le temps requis est un paramètre de l’activité, pas le temps réellement passé par un apprenant.
- L’option de remise par courriel est celle de l’activité native. Elle n’envoie pas automatiquement les corrections préparées par le plugin : l’envoi d’une correction reste une action explicite.

## Accès et confidentialité

Le rapport, la source de rapports personnalisés et leurs exports restent réservés aux administrateurs du site. Ajouter une audience à un rapport ne donne pas accès à ces données aux autres utilisateurs.

Les colonnes de destinataires et de texte peuvent contenir des renseignements personnels saisis dans l’activité. Protéger les exports et ne sélectionner ces colonnes que si elles sont nécessaires. Le plugin ne conserve pas une seconde copie de ces réglages et ne les transmet à aucun service externe.

## Exemple de rapport ciblé dans Moodle

Créer un rapport nommé « Certificats IOMAD — Modèle, date et courriel » avec six colonnes : Cours, Activité, Identifiant du modèle actuel, Source de la date imprimée, Format de la date imprimée et Mode de remise natif. Pour exiger la date d’émission au format « 1 janvier 2000 » et la remise par courriel, vérifier les valeurs Date d’émission, Jour mois année et Courriel. Certains libellés dépendent des traductions du module IOMAD Certificate installé.

Le menu « Télécharger les données au format », sous le tableau, propose les formats natifs activés dans Moodle. L’export porte sur les résultats selon les filtres du rapport. Chaque rapport peut sélectionner un sous-ensemble de colonnes; l’inventaire complet reste disponible séparément.
