# Notes de Frais - Subterra

Ce projet est une application de gestion des notes de frais développée pour l'entreprise Subterra.

## Fonctionnalités principales
- Création et gestion des notes de frais
- Archivage et synthèse des notes
- Gestion des utilisateurs et des types de dépenses
- Génération de PDF et envoi d'e-mails
- Interface utilisateur moderne avec Bootstrap et FontAwesome

## Structure du projet
- `controllers/` : Contrôleurs PHP pour la logique métier
- `services/` : Services pour la gestion des données, PDF, mails, etc.
- `views/` : Vues PHP pour l'affichage
- `assets/` : Fichiers JS, CSS, et bibliothèques front-end
- `vendor/` : Dépendances gérées par Composer

## Installation
1. Cloner le dépôt
2. Installer les dépendances avec Composer :
   ```bash
   composer install
   ```
3. Configurer le fichier `.env` pour la base de données et les paramètres SMTP
4. Lancer le serveur web local ou déployer sur votre infrastructure

## Utilisation
Accédez à l'application via votre navigateur. Les différents modules sont accessibles depuis le menu principal.

## Dépendances principales
- PHP >= 7.4
- Composer
- Bootstrap
- FontAwesome
- TCPDF

## Auteur
Développé pour Subterra.

## Licence
Voir le fichier LICENSE pour les détails.
