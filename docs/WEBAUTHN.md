# WebAuthn / Passkeys

BAuth supporte WebAuthn (FIDO2, U2F) pour l'authentification sans mot de passe via des clés de sécurité et des passkeys.

## Installation

WebAuthn est inclus par défaut dans BAuth.

### Dépendances optionnelles

Pour une meilleure support de WebAuthn, vous pouvez installer la librairie `web-auth/webauthn-lib`:

```bash
composer require web-auth/webauthn-lib
```

## Configuration

### Configuration de base

```php
<?php

use Bmvc\BAuth\Providers\BaseWebAuthnProvider;

$webauthn = new BaseWebAuthnProvider('https://yourapp.com');
```

### Avec Laravel

```php
<?php

use Bmvc\BAuth\Adapters\Laravel\LaravelWebAuthnProvider;

$webauthn = new LaravelWebAuthnProvider(
    env('APP_URL'),
    'App\Models\WebAuthnCredential',
    'App\Models\WebAuthnBackupCode'
);
```

### Avec Symfony

```php
<?php

use Bmvc\BAuth\Adapters\Symfony\SymfonyWebAuthnProvider;

$webauthn = new SymfonyWebAuthnProvider(
    $entityManager,
    'https://yourapp.com',
    'App\Entity\User',
    'App\Entity\WebAuthnCredential',
    'App\Entity\WebAuthnBackupCode'
);
```

## Utilisation

### 1. Démarrer l'enregistrement d'une clé WebAuthn

```php
<?php

$challenge = $webauthn->startRegistration(
    auth()->id(),
    auth()->user()->email,
    auth()->user()->name
);

// Envoyer le défi au navigateur
echo json_encode($challenge);
```

### 2. Compléter l'enregistrement d'une clé WebAuthn

```php
<?php

$attestationResponse = json_decode($_POST['attestation_response'], true);

try {
    $credential = $webauthn->completeRegistration(
        auth()->id(),
        'Ma clé de sécurité',
        $attestationResponse
    );

    echo "Clé WebAuthn enregistrée avec succès!";
} catch (Exception $e) {
    echo "Erreur: " . $e->getMessage();
}
```

### 3. Démarrer l'authentification WebAuthn

```php
<?php

$challenge = $webauthn->startAuthentication($_POST['username']);

// Envoyer le défi au navigateur
echo json_encode($challenge);
```

### 4. Compléter l'authentification WebAuthn

```php
<?php

$assertionResponse = json_decode($_POST['assertion_response'], true);

try {
    $user = $webauthn->completeAuthentication(
        $_POST['username'],
        $assertionResponse
    );

    auth()->login($user);
    echo "Authentification réussie!";
} catch (Exception $e) {
    echo "Erreur: " . $e->getMessage();
}
```

### 5. Obtenir les credentials enregistrés d'un utilisateur

```php
<?php

$credentials = $webauthn->getUserCredentials(auth()->id());

foreach ($credentials as $credential) {
    echo $credential['credential_name'] . " - " . $credential['created_at'];
}
```

### 6. Supprimer une credential WebAuthn

```php
<?php

$webauthn->deleteCredential(auth()->id(), $credentialId);

echo "Credential supprimée";
```

### 7. Renommer une credential WebAuthn

```php
<?php

$webauthn->renameCredential(auth()->id(), $credentialId, 'Mon nouveau nom');
```

### 8. Vérifier le statut de sauvegarde d'une credential

```php
<?php

$backupStatus = $webauthn->getCredentialBackupStatus(auth()->id(), $credentialId);

echo "Sauvegarde possible: " . ($backupStatus['backup_eligible'] ? "Oui" : "Non");
echo "Sauvegardée: " . ($backupStatus['backup_state'] ? "Oui" : "Non");
```

### 9. Générer des codes de secours

```php
<?php

$backupCodes = $webauthn->generateBackupCodes(auth()->id(), 10);

foreach ($backupCodes as $code) {
    echo $code . "\n";
}

// Afficher à l'utilisateur pour qu'il les sauvegarde
```

### 10. Valider un code de secours

```php
<?php

if ($webauthn->validateBackupCode(auth()->id(), $_POST['backup_code'])) {
    // Code valide, permettre l'accès
    auth()->login($user);
} else {
    // Code invalide
    echo "Code de secours invalide";
}
```

### 11. Obtenir les codes de secours d'un utilisateur

```php
<?php

$backupCodes = $webauthn->getUserBackupCodes(auth()->id());

// Les codes sont masqués pour la sécurité
foreach ($backupCodes as $code) {
    echo $code['code']; // ex: "****-ABCD"
}
```

### 12. Vérifier si l'authentification sans mot de passe est activée

```php
<?php

if ($webauthn->isPasswordlessEnabled(auth()->id())) {
    echo "Vous pouvez vous connecter sans mot de passe";
}
```

### 13. Activer/désactiver l'authentification sans mot de passe

```php
<?php

$webauthn->setPasswordlessEnabled(auth()->id(), true);
```

## Implémentation JavaScript côté client

### Enregistrement

```javascript
async function registerWebAuthn() {
  // Obtenir le défi du serveur
  const response = await fetch("/webauthn/start-register", {
    method: "POST",
  });
  const challenge = await response.json();

  // Créer la credential
  const credential = await navigator.credentials.create({
    publicKey: {
      challenge: Uint8Array.from(atob(challenge.challenge), (c) =>
        c.charCodeAt(0),
      ),
      rp: challenge.rp,
      user: {
        id: Uint8Array.from(atob(challenge.user.id), (c) => c.charCodeAt(0)),
        name: challenge.user.name,
        displayName: challenge.user.displayName,
      },
      pubKeyCredParams: challenge.pubKeyCredParams,
      timeout: challenge.timeout,
      attestation: challenge.attestation,
    },
  });

  if (!credential) {
    console.error("Enregistrement annulé");
    return;
  }

  // Envoyer au serveur
  const registerResponse = await fetch("/webauthn/complete-register", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      credential_name: document.getElementById("device-name").value,
      attestation_response: {
        id: credential.id,
        rawId: btoa(
          String.fromCharCode.apply(null, new Uint8Array(credential.rawId)),
        ),
        response: {
          attestationObject: btoa(
            String.fromCharCode.apply(
              null,
              new Uint8Array(credential.response.attestationObject),
            ),
          ),
          clientDataJSON: btoa(
            String.fromCharCode.apply(
              null,
              new Uint8Array(credential.response.clientDataJSON),
            ),
          ),
        },
      },
    }),
  });

  const result = await registerResponse.json();
  if (result.success) {
    console.log("Enregistrement réussi");
  }
}
```

### Authentification

```javascript
async function authenticateWebAuthn(username) {
  // Obtenir le défi du serveur
  const response = await fetch("/webauthn/start-auth", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({ username: username }),
  });
  const challenge = await response.json();

  // Obtenir la credential
  const assertion = await navigator.credentials.get({
    publicKey: {
      challenge: Uint8Array.from(atob(challenge.challenge), (c) =>
        c.charCodeAt(0),
      ),
      timeout: challenge.timeout,
      rpId: challenge.rpId,
      allowCredentials: challenge.allowCredentials.map((cred) => ({
        type: "public-key",
        id: Uint8Array.from(atob(cred.id), (c) => c.charCodeAt(0)),
      })),
    },
  });

  if (!assertion) {
    console.error("Authentification annulée");
    return;
  }

  // Envoyer au serveur
  const authResponse = await fetch("/webauthn/complete-auth", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      username: username,
      assertion_response: {
        id: assertion.id,
        rawId: btoa(
          String.fromCharCode.apply(null, new Uint8Array(assertion.rawId)),
        ),
        response: {
          authenticatorData: btoa(
            String.fromCharCode.apply(
              null,
              new Uint8Array(assertion.response.authenticatorData),
            ),
          ),
          clientDataJSON: btoa(
            String.fromCharCode.apply(
              null,
              new Uint8Array(assertion.response.clientDataJSON),
            ),
          ),
          signature: btoa(
            String.fromCharCode.apply(
              null,
              new Uint8Array(assertion.response.signature),
            ),
          ),
        },
      },
    }),
  });

  const result = await authResponse.json();
  if (result.success) {
    window.location.href = "/dashboard";
  }
}
```

## Workflow d'authentification sans mot de passe complet

### 1. Enregistrement

1. Utilisateur navigue vers les paramètres de sécurité
2. Clique sur "Ajouter une clé de sécurité"
3. Choisit le nom du dispositif
4. Le navigateur affiche une interface pour enregistrer la clé
5. Utilisateur se présente avec la clé (empreinte digitale, NIP, etc.)
6. Le serveur valide et sauvegarde la clé
7. Afficher les codes de secours pour imprimer/sauvegarder

### 2. Authentification

1. Utilisateur arrive sur la page de connexion
2. Entre son nom d'utilisateur
3. Clique sur "Se connecter avec WebAuthn"
4. Le navigateur demande la présentation de la clé
5. Utilisateur se présente avec la clé
6. Redirection vers le tableau de bord
7. Session créée

### 3. Récupération (codes de secours)

1. Si la clé est perdue/inaccessible
2. Utilisateur utilise un code de secours
3. Authentification réussie
4. L'utilisateur est invité à enregistrer une nouvelle clé
5. Les codes de secours restants sont affichés

## Support des navigateurs

- Chrome 67+
- Firefox 60+
- Safari 13+
- Edge 18+
- Android Chrome
- iOS Safari 13+

## Sécurité

- WebAuthn est une norme W3C sécurisée
- Les clés de sécurité ne quittent jamais le dispositif
- Le secret n'est jamais partagé avec le serveur
- Protection contre le phishing (vérification du domaine)
- Protection contre les replay attacks
- Authentification à deux facteurs par défaut

## Bonnes pratiques

1. Toujours avoir au moins une clé WebAuthn enregistrée
2. Offrir des codes de secours
3. Permettre l'enregistrement de plusieurs clés
4. Afficher le statut de la clé (sauvegardée, non sauvegardée)
5. Permettre le renommage des clés
6. Enregistrer les événements d'authentification
7. Implémenter une limite de taux

## Variables d'environnement

```env
WEBAUTHN_RP_NAME=Mon Application
WEBAUTHN_RP_ID=yourapp.com
WEBAUTHN_ORIGIN=https://yourapp.com
WEBAUTHN_BACKUP_CODES_COUNT=10
WEBAUTHN_REQUIRE_BACKUP=true
WEBAUTHN_ATTESTATION=direct
```

## Limitation connue

- Les passkeys synchronisés ne sont pas encore complètement standardisés
- Le support du cloud backup varie selon les navigateurs
- La révocation de clé n'est pas possible côté serveur
