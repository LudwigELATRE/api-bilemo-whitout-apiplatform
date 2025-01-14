# BileMo API

BileMo est une entreprise proposant une vaste gamme de téléphones mobiles haut de gamme. Ce projet consiste à développer une API REST permettant à des clients B2B d'accéder au catalogue de produits et de gérer leurs utilisateurs.

## Table des matières

- [Contexte](#contexte)
- [Fonctionnalités](#fonctionnalités)
- [Spécifications techniques](#spécifications-techniques)
- [Installation et utilisation](#installation-et-utilisation)
- [Authentification](#authentification)
- [Modèle de Richardson](#modèle-de-richardson)
- [Contribution](#contribution)
- [Licence](#licence)

---

## Contexte

BileMo propose une solution exclusivement B2B. L'objectif est de permettre à des plateformes partenaires d'accéder aux données suivantes :

1. Liste des produits BileMo.
2. Détails d'un produit spécifique.
3. Liste des utilisateurs liés à un client.
4. Détails d'un utilisateur lié à un client.
5. Ajout d'un nouvel utilisateur.
6. Suppression d'un utilisateur existant.

Seuls les clients authentifiés peuvent accéder à l'API.

---

## Fonctionnalités

- **Consultation des produits** : Récupérez la liste des produits et les détails d'un produit en particulier.
- **Gestion des utilisateurs** : Consultez, ajoutez ou supprimez des utilisateurs liés à un client.
- **Authentification sécurisée** : Gestion des tokens JWT pour accéder aux ressources de manière sécurisée.
- **Conformité avec le modèle de Richardson** : Implémentation des niveaux 1, 2 et 3.
- **Performance** : Mise en cache des réponses pour des requêtes optimisées.

---

## Spécifications techniques

- **Backend** : Symfony 6.3
- **Langage** : PHP 8.2
- **Authentification** : JWT via [LexikJWTAuthenticationBundle](https://github.com/lexik/LexikJWTAuthenticationBundle)
- **Format des réponses** : JSON
- **Base de données** : MySQL 8+
- **Cache** : Utilisation de cache HTTP pour optimiser les performances des requêtes.

---

## Installation et utilisation

### Prérequis

- PHP 8.2 ou plus
- Composer
- Symfony CLI
- Serveur web (Apache/Nginx) avec mod_rewrite activé
- MySQL 8+ ou autre base de données compatible

### Installation

1. Clonez le dépôt :

   ```bash
   git clone https://github.com/username/nom_du_repo.git
   cd nom_du_repo
   ```

2. Installez les dépendances :

   ```bash
   composer install
   ```

3. Configurez les variables d'environnement :

   Créez un fichier `.env.local` et ajoutez vos paramètres :

   ```env
   DATABASE_URL="mysql://username:password@127.0.0.1:3306/nom_de_la_base"
   JWT_SECRET_KEY="%kernel.project_dir%/config/jwt/private.pem"
   JWT_PUBLIC_KEY="%kernel.project_dir%/config/jwt/public.pem"
   JWT_PASSPHRASE="votre_passphrase"
   ```

4. Initialisez la base de données :

   ```bash
   php bin/console doctrine:database:create
   php bin/console doctrine:migrations:migrate
   php bin/console doctrine:fixtures:load
   ```

6. Lancez le serveur local :

   ```bash
   symfony server:start
   ```

### Utilisation

Accédez à l'API via l'URL : `http://127.0.0.1:8000/api/doc`

---

## Authentification

L'authentification est gérée via JWT. Voici les étapes pour obtenir un token :

1. Envoyez une requête POST à `/api/login_check` avec les identifiants suivants :

   ```json
   {
       "username": "nom entreprise",
       "password": "password"
   }
   ```

2. Utilisez le token retourné pour accéder aux endpoints protégés :

   ```http
   Authorization: Bearer <votre_token>
   ```

---

## Modèle de Richardson

L'API respecte les trois niveaux du modèle de maturité de Richardson :

1. **Niveau 1** : Organisation en ressources avec des endpoints clairs.
2. **Niveau 2** : Utilisation des verbes HTTP appropriés (GET, POST, DELETE, etc.).
3. **Niveau 3** : Hypermedia (HATEOAS) pour guider les clients dans l'utilisation de l'API.

---

## Contribution

Les contributions sont les bienvenues !

1. Forkez le dépôt.
2. Créez une branche pour votre fonctionnalité ou correctif :

   ```bash
   git checkout -b feature/ma-fonctionnalite
   ```

3. Faites vos modifications et testez-les.
4. Soumettez une pull request pour examen.

---

## Documentation Technique 

- http://localhost:8000/api/doc


---

## Licence

Ce projet est sous licence MIT. Consultez le fichier `LICENSE` pour plus d'informations.

