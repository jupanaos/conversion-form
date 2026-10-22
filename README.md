# Convertisseur de fichier (TXT → CSV)

Il s'agit d'une application web qui convertit un export de ventes comptables (`.txt`) en fichier CSV. Appliquée à `VENTE_ORIGINE.TXT`, la conversion produit un fichier identique octet pour octet à `VENTE_FINAL.csv` (fichier d'origine pris pour exemple).

| Couche | Technologie |
|---|---|
| Front | Angular 22, ng-zorro-antd |
| Back | Symfony 7.4 LTS (PHP 8.4) |
| Infra | Docker Compose |

L'API est sans état : aucune base de données, aucun fichier conservé. Les fichiers sont limités à 2 Mo.

## Lancement

**Prérequis** : Docker avec Compose, `make`, ports `4200` et `8000` libres.

```bash
make up
```

Le premier démarrage construit les images.

- Application : http://localhost:4200

Arrêt : `make down`.

**Utilisation** : déposer un fichier `.txt` dans la zone prévue, puis cliquer sur "Convertir". Le CSV est téléchargé ; si le fichier est invalide, l'erreur indique le numéro de ligne incorrecte.

La configuration est celle d'un environnement de développement, pas d'une production.

## Règles de conversion

Règles déduites de l'analyse des deux fichiers, vérifiées sur les 323 lignes de l'échantillon (aucun écart).

- **Entrée** : une écriture par ligne, 9 champs séparés par `;` (le 9ᵉ est vide), sans en-tête, en Windows-1252 ou UTF-8. Les champs sont débarrassés de leurs espaces de début et de fin.
- **Sortie** : en-tête de 11 colonnes, UTF-8 avec BOM, fins de ligne LF. L'ordre des lignes est conservé et les lignes vides sont ignorées.

| Colonne du CSV | Champ du TXT (n°) | Règle |
|---|---|---|
| Numéro d'écriture | Pièce (5) | Recopié |
| Compte | Auxiliaire (4) ou compte (3) | L'auxiliaire s'il est renseigné, sinon le compte |
| Compte libellé | — | Toujours vide |
| Date de pièce | Date (2) | `jj/mm/aaaa` → `aaaammjj` |
| Journal | Journal (1) | Recopié |
| Référence | Pièce (5) | Identique au numéro d'écriture |
| Ecriture libellé | Libellé (6) | Suites d'espaces réduites à un seul |
| Crédit | Crédit (8) | Recopié tel quel (`0.00` conservé) |
| Débit | Débit (7) | Recopié tel quel |
| Lettre | — | Toujours vide |
| NomFichier | — | Toujours vide |

Le débit et le crédit sont inversés en position entre la source (débit, crédit) et le CSV (crédit, débit).

## Hypothèses et ambiguïtés

Aucune confirmation du métier n'a pu être obtenue : ces points sont des hypothèses retenues, à valider.

- **Sous-compte** : renseigné uniquement sur les comptes de tiers et dans ce cas il remplace le compte "parent". Vérifié sur l'échantillon (79 lignes avec sous-compte, toutes `411000`), mais un sous-compte saisi par erreur sur un compte "hors tiers" remplacerait le compte parent sans alerte.
- **Fins de ligne** : LF, comme l'échantillon, alors que les utilisateurs travaillent sous Windows (Excel). CRLF à confirmer.
- **Encodage** : l'entrée est détectée automatiquement (UTF-8 ou Windows-1252). Les autres encodages ne sont pas gérés.
- **Colonnes vides** : `Compte libellé`, `Lettre` et `NomFichier` sont toujours vides dans l'échantillon. `NomFichier` devrait-il contenir le nom du fichier source ?
- **Montants** : format `123.45` exigé, les négatifs et les autres formats sont rejetés.
- **Journal** : seul `V1` (ventes) a été vu. Les autres valeurs sont recopiées, sans avoir été testées.
- **Équilibre débit/crédit** : vrai par pièce sur l'échantillon, non contrôlé.
- **Structure du fichier** : 9 champs dans l'ordre de l'échantillon, sans en-tête (une ligne de titres est rejetée). Seule la forme des champs est contrôlée, pas leur sens. Un `;` dans un libellé n'est pas géré.
- **Erreurs** : la conversion s'arrête à la première ligne invalide.
- **Formules Excel** : un libellé commençant par `=`, `+`, `-` ou `@` n'est pas neutralisé, car cela romprait l'identité avec le CSV attendu.

## Usage de l'IA

J'ai utilisé Claude (Claude Code) comme interlocuteur et relecteur. Il a analysé les deux fichiers, vérifié les règles par script sur les 323 lignes, aidé à construire un plan d'action et à prendre des notes, lancé les vérifications (tests, comparaison octet pour octet) et rédigé la documentation et le `Makefile`. J'ai écrit moi-même le code applicatif et les tests, et pris les décisions finales.

Le test de référence (`VENTE_ORIGINE.TXT` converti, comparé octet pour octet à `VENTE_FINAL.csv`) est un contrôle objectif : il ne dépend pas de l'auteur du code.