# Contribution à BAuth

Merci de votre intérêt pour contribuer à BAuth ! Ce document fournit des directives et des instructions pour contribuer au projet.

## Code de conduite

Ce projet et tous les participants à celui-ci sont régis par notre Code de conduite. En participant, vous êtes censé respecter ce code.

## Comment contribuer

### Signaler des bugs

Les bugs sont suivis via les issues GitHub. Avant de créer un rapport, veuillez vérifier que le problème n'a pas déjà été rapporté.

Quand vous signalez un bug, incluez :

- Un titre clair et descriptif
- Une description détaillée du comportement observé
- Des étapes pour reproduire le problème
- Un exemple spécifique pour démontrer les étapes
- Votre environnement (OS, version PHP, etc.)

### Suggérer des améliorations

Les améliorations peuvent être proposées via les issues GitHub. Incluez :

- Un titre clair et descriptif
- Une description détaillée du comportement souhaité
- Les cas d'usage
- Des implémentations possibles

### Soumettre des modifications

1. Créez un fork du dépôt
2. Créez une branche pour votre modification (`git checkout -b feature/AmazingFeature`)
3. Commitez vos modifications (`git commit -m 'Add AmazingFeature'`)
4. Poussez vers la branche (`git push origin feature/AmazingFeature`)
5. Ouvrez une Pull Request

## Directives de développement

### Standards de code

- Suivez les standards PSR-1 et PSR-2
- Utilisez 4 espaces pour l'indentation
- Écrivez des noms de classe en PascalCase
- Écrivez des noms de méthode en camelCase
- Documentez le code avec des commentaires PHPDoc

### Tests

- Écrivez des tests unitaires pour toutes les nouvelles fonctionnalités
- Assurez-vous que tous les tests passent avant de soumettre
- Maintenez une couverture de code d'au moins 80%

```bash
composer test
```

### Linting

```bash
composer phpstan
composer phpcs
```

### Messages de commit

- Utilisez l'impératif ("Add feature" pas "Added feature")
- Limitez la première ligne à 50 caractères
- Référencez les issues et pull requests libéralement

Exemple :

```
Add JWT token refresh endpoint

This allows users to refresh their tokens without re-authenticating.
Fixes #123
```

## Configuration du développement

1. Clonez le dépôt
2. Installez les dépendances : `composer install`
3. Créez un fichier `.env` basé sur `.env.example`
4. Exécutez les tests : `composer test`

## Structure du projet

```
src/
├── Auth.php              # Classe principale
├── Config.php            # Configuration
├── Contracts/            # Interfaces
├── Providers/            # Implémentations
├── Support/              # Utilitaires
├── Examples/             # Exemples d'intégration
└── Exceptions/           # Exceptions personnalisées

tests/
└── AuthTest.php          # Tests

examples/
├── quick-start.php       # Démarrage rapide
├── api-rest.php          # API REST
└── middleware.php        # Middleware
```

## Licence

En contribuant à BAuth, vous acceptez que vos contributions soient licenciées sous la licence MIT.

## Questions ?

N'hésitez pas à ouvrir une issue ou à nous contacter. Nous sommes là pour aider !
