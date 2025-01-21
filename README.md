# **Code2Gen - Automatic HTML, CSS, and JS Generator**

## **Description**

**Code2Gen** est un outil qui permet de générer automatiquement du code HTML, CSS, et JavaScript à partir d'une description textuelle fournie par l'utilisateur. Il utilise une API de modèle de langage (par exemple OpenAI GPT-4) pour générer du code web complet et le rendre disponible via un lien de téléchargement.

Si la clé API n'est pas définie, l'application proposera un fichier HTML aléatoire à partir d'un dossier de builds existants.

## **Fonctionnalités**

- Génération automatique d'une page web complète (HTML, CSS, JS inline) basée sur une description utilisateur.
- Sauvegarde des builds dans un dossier dédié (`builds`) avec un numéro de build incrémental.
- Possibilité d'afficher un fichier HTML aléatoire si aucune clé API n'est définie.
- Interface utilisateur simple et moderne.
- Loader animé pour indiquer à l'utilisateur que la page est en cours de génération.

## **Technologies Utilisées**

- **Frontend** : HTML, SCSS (CSS compilé), JavaScript.
- **Backend** : PHP.
- **API de génération** : OpenAI GPT-4

## **Installation**

### **Prérequis**

- **PHP** 7.4 ou supérieur
- **Serveur Web** (Apache, Nginx ou PHP built-in server)
- **OpenAI API Key** (si vous voulez utiliser GPT-4 pour générer du code)

### **Étapes d'installation**

1. **Cloner le dépôt**

   ```bash
   git clone https://github.com/leochiron/code2Gen.git
   cd code2gen
   ```

2. **Configurer la clé API**

   Créez un fichier `config.php` à la racine du projet (ou utilisez celui déjà fourni) et ajoutez-y votre clé OpenAI API :

   ```php
   <?php 
   // config.php
   return [
      'ia_used' => 'gemini',//openai or gemini
      'api_key_open_ai' => 'sk-votre-clé-api-ici',
      'api_key_gemini' => 'sk-votre-clé-api-ici',
      'gemini_model' => 'gemini-1.5-flash-8b' // gemini-1.5-flash-8b or gemini-1.5-flash-latest
   ];
   ```

   Si vous ne définissez pas de clé API, le système proposera un fichier HTML aléatoire dans le dossier `builds` au lieu de générer une nouvelle page.

3. **Assurez-vous que le dossier `builds` existe**

   Le dossier `builds` doit exister pour stocker les fichiers HTML générés. S'il n'existe pas, créez-le manuellement :

   ```bash
   mkdir builds
   chmod 775 builds
   ```

4. **Lancer le projet**

   Si vous utilisez le serveur PHP intégré, vous pouvez démarrer le projet localement en exécutant la commande suivante :

   ```bash
   php -S localhost:8000
   ```

   Le projet sera accessible à l'adresse `http://localhost:8000`.

## **Utilisation**

1. **Accéder à l'interface utilisateur**

   Ouvrez votre navigateur et accédez à `http://localhost:8000`. Vous verrez une interface vous permettant d'entrer une description textuelle de la page web que vous souhaitez générer.

2. **Générer une page web**

   - Entrez une description dans le champ prévu et cliquez sur **"Generate Code"**.
   - Un loader apparaîtra pendant la génération.
   - Une fois la génération terminée, un lien apparaîtra pour télécharger ou visualiser le fichier HTML généré.

3. **Cas où aucune clé API n'est définie**

   Si vous n'avez pas configuré de clé API dans le fichier `config.php`, l'outil sélectionnera aléatoirement un fichier dans le dossier `builds` et affichera un lien pour visualiser ce fichier.

## **Exemple de description**

Vous pouvez entrer des descriptions comme :

- "A simple landing page with a hero section, call to action, and a footer."
- "A contact form with name, email, and message fields. Include validation and a submit button."

Le modèle générera un fichier HTML complet avec CSS et JavaScript inline basé sur cette description.

## **Structure du projet**

```
/code2gen
├── /builds                 # Dossier où sont stockés les fichiers HTML générés
├── config.php              # Fichier de configuration des variables d'environnement (clé API)
├── generate.php            # Script principal pour la génération de code et gestion des builds
├── index.html              # Page frontend (interface utilisateur)
├── styles.css              # Styles CSS générés par SCSS
├── styles.scss             # Styles SCSS pour générés le CSS
├── script.js               # Script JavaScript pour gérer le frontend et les requêtes
└── README.md               # Documentation du projet
```

## **Personnalisation**

Vous pouvez ajuster les styles dans le fichier SCSS pour modifier l'apparence de l'interface utilisateur. Les fichiers générés sont actuellement en HTML avec CSS et JavaScript inline, mais vous pouvez modifier cela si vous voulez séparer les fichiers CSS et JS dans `generate.php`.

## **Problèmes Courants**

1. **Pas de réponse de l'API OpenAI** :
   - Vérifiez que votre clé API est correctement définie dans le fichier `config.php`.
   - Assurez-vous que votre serveur dispose d'un accès Internet.

2. **Le fichier généré ne contient pas de contenu valide** :
   - Cela peut arriver si la description fournie n'est pas assez claire. Essayez de fournir plus de détails.

## **Licence**

Ce projet est sous licence MIT. Vous êtes libre de l'utiliser, de le modifier et de le distribuer.
