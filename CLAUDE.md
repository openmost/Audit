# Plugin Matomo « Audit » (Openmost, version gratuite)

Plugin Matomo On-Premise qui introspecte l'instance sur laquelle il est installé et produit un rapport d'audit de configuration structuré (page d'administration + export Markdown).

Version **gratuite**, publiée sur le Marketplace Matomo (dépôt public `openmost/Audit`). Elle est issue du plugin payant `AuditPremium` (dépôt privé `openmost/AuditPremium`, page produit https://openmost.com/matomo/extensions/audit) : elle exécute 53 checks et affiche les 96 autres avec un badge « Premium » sans les exécuter. Le découpage est décrit dans `AuditPremium/docs/superpowers/specs/2026-09-16-audit-lite-premium-design.md`.

Le plugin est un **runtime pour une checklist** (`Checklist/checklist.yaml`) : la logique métier vit dans la checklist et les classes de checks, le plugin se contente de les exécuter dans le contexte Matomo.

---

## Gratuit / premium : règles du découpage

- **Checks gratuits (53)** : catégories `infrastructure`, `php`, `database`, `config-file`, `general`, `ops`, sauf les 4 checks qui demandent des sondes HTTP (`srv-ssl-cert-rating`, `srv-waf-cdn`, `srv-web-http2`, `srv-web-https-force`).
- **Checks premium (96)** : `privacy`, `users`, `sites`, `data-quality`, `tag-manager`, `plugins`, `files-exposure`, `high-traffic`, `multi-server`, plus les 4 checks HTTP ci-dessus.
- **Règle anti-fuite** : un item premium ne garde dans `checklist.yaml` que `id`, `cat`, `severity` et `premium: true`. Pas de classe PHP, pas de `methods`, règles de détection, `refs`, `code` ni `template`. Dans les 7 fichiers `lang/*.json`, seule la clé `<Slug>Title` existe, jamais de `<Slug>Template` ni `<Slug>Detail*`.
- Pas de sondes HTTP dans ce plugin : pas de `HttpProber`, pas de `HttpAwareInterface`, pas de case « HTTP probes », pas d'option `--allow-http`. Aucune référence à `AuditPremium_` ni au namespace `Piwik\Plugins\AuditPremium`.
- `tests/Unit/PremiumLeakTest.php` garde tout cela : répartition gratuit / premium par catégorie, clés YAML autorisées, absence de classe pour les items premium (et une classe pour chaque item gratuit), textes premium absents des fichiers de langue, aucune trace de `HttpProber` ou de code AuditPremium.
- **Les correctifs et nouveaux checks gratuits viennent d'AuditPremium** : on corrige d'abord dans `AuditPremium`, puis on reporte ici (classe, clés de langue, entrée YAML) en renommant `AuditPremium` en `Audit`. Ne pas faire évoluer un check gratuit uniquement dans ce plugin.
- Un item premium est rendu par `CheckRunner::run()` comme un `CheckResult` de statut `premium` (titre + sévérité, sans résultat). `MarkdownExporter` liste ces items à la fin, par catégorie, sous « Premium checks (not run) », avec le lien `Audit::PREMIUM_URL`.

## Coexistence avec AuditPremium

`Audit::isPremiumActivated()` teste si `AuditPremium` est actif. Dans ce cas le plugin gratuit s'efface :

- `Menu.php` : pas d'entrée de menu.
- `Controller::index()` : redirection vers `module=AuditPremium&action=index`.
- `Audit::filterConsoleCommands()` (événement `Console.filterCommands`) : retire les commandes `Piwik\Plugins\Audit\...`, car les deux plugins déclarent `audit:run`.

Côté AuditPremium, la page affiche une notification qui propose de désactiver le plugin gratuit.

## Cible technique

- **Matomo 6** (`>=6.0.0-b1,<7.0.0-b1`), branche principale `6.x-dev`. La version 6.0.0 est la première version du plugin gratuit.
- **PHP 8.1+** (minimum de Matomo 6) : pas de syntaxe PHP 8.2+ (readonly classes, types `true`/`false`/`null` autonomes, DNF types).
- **MySQL 8.0+ / MariaDB 10.6+**.
- Namespace racine : `Piwik\Plugins\Audit`, style PSR-12.
- **Aucune dépendance Composer** : pur PHP + APIs Matomo (parser YAML maison dans `Checklist/YamlParser.php`).
- Pas de `declare(strict_types=1)` dans `Audit.php` (le contrôle de publication du Marketplace échoue).
- APIs Matomo 6 : `Piwik\Request::fromRequest()` pour lire les paramètres (jamais `Common::getRequestVar()`, déprécié).

## Architecture

```
Audit/
├── Audit.php                 # bootstrap : stylesheet, clés de traduction front, filtre des commandes, PREMIUM_URL
├── Menu.php                  # entrée Administration > Diagnostic > Audit (super user, masquée si AuditPremium actif)
├── Controller.php            # index (page Vue, redirection si AuditPremium actif) + exportMarkdown
├── Checklist/                # checklist.yaml (149 items dont 96 premium), ChecklistItem, ChecklistLoader, YamlParser
├── Checks/
│   ├── CheckInterface.php, AbstractCheck.php, CheckResult.php, CheckRunner.php, AuditReport.php
│   ├── MetricsAwareInterface.php
│   └── PHP/ Database/ ConfigFile/ Infrastructure/ General/ Operations/
├── Context/InstanceMetrics.php   # taille de base, volumes, palier de dimensionnement
├── Support/                  # CheckTranslator, InlineMarkdown
├── Export/MarkdownExporter.php   # résultats gratuits puis liste des checks premium
├── Commands/                 # audit:run, audit:debug-metrics, audit:debug-translations
├── templates/index.twig      # point de montage vue-entry dans la page admin
├── vue/src/                  # AuditReport.vue, Finding.vue (Vue 3 + TypeScript, badge et filtre « Premium »)
├── vue/dist/Audit.umd.min.js # bundle Vite commité
├── stylesheets/audit.less
├── lang/                     # en, fr, de, zh-cn, it, es, sv
└── tests/                    # tests unitaires autonomes (tests/bootstrap.php), dont PremiumLeakTest
```

- `CheckRunner` résout la classe d'un item par préfixe d'id (`srv-php-*` → `Checks\PHP`, `srv-mysql-*` → `Checks\Database`, `srv-*` → `Checks\Infrastructure`, `cfg-*` → `Checks\ConfigFile`, `gen-*` → `Checks\General`, `ops-*` → `Checks\Operations`) puis kebab → PascalCase + `Check`. Les exceptions vont dans `CheckRunner::$classSuffixOverrides`. Un item premium n'est jamais résolu.
- Chaque check étend `AbstractCheck` et renvoie un `CheckResult` via `pass()`, `fail()`, `warn()`, `skip()`. Un check reste léger : introspection + construction du résultat.

## Traductions : règle absolue

**Aucun texte visible par l'utilisateur n'est écrit en dur.** 7 langues : `en`, `fr`, `de`, `zh-cn`, `it`, `es`, `sv`.

- Titres et recommandations : clés `Audit_<Slug>Title` et `Audit_<Slug>Template`, où `<Slug>` est l'id de l'item en PascalCase (`srv-php-version` → `SrvPhpVersion`).
- Messages de détail : `$this->t($item, 'variante', $vars, 'Fallback anglais')`, résolu par `Support\CheckTranslator` en `Audit_<Slug>Detail<Variante>`. Placeholders `{nom}` alimentés par `$vars`.
- Toute nouvelle clé est ajoutée **dans les 7 fichiers** `lang/*.json`. Le fallback anglais du code est identique à la valeur de `en.json`.
- Items premium : uniquement `<Slug>Title` (voir règle anti-fuite).
- Seules les clés d'interface sont envoyées au navigateur (`Translate.getClientSideTranslationKeys`), les textes des findings sont résolus côté serveur.
- Vérifier avec `php console audit:debug-translations --locale=<code>`.

## Règles strictes

- **100 % lecture seule** : jamais d'écriture dans `config.ini.php`, en base ou dans un fichier, pas de cache persistant des résultats.
- Requêtes via `Piwik\Db::fetchAll()` / `fetchOne()`, jamais PDO direct. Rester sur les métadonnées (`information_schema`, `SHOW VARIABLES`, options).
- Pas de `shell_exec` ni d'appel CLI externe, pas de télémétrie, **aucun appel HTTP sortant**.
- Toutes les actions du contrôleur commencent par `Piwik::checkUserHasSuperUserAccess()`.
- Les recommandations suivent les exigences de Matomo 6 (PHP 8.1 minimum, 8.2+ recommandé ; MySQL 8.0+ / MariaDB 10.6+).

## Développement

```bash
cd /chemin/vers/matomo

# Front : build Vite (Node 24) puis lint
php console vue:build Audit
npx eslint plugins/Audit/vue/src --ext .ts,.vue

# Tests unitaires (sans installation Matomo complète), PremiumLeakTest compris
php vendor/bin/phpunit -c plugins/Audit/tests/phpunit.xml.dist

# Exécution réelle de l'audit
php console audit:run
php console audit:run --only=srv-php-version,cfg-force-ssl
php console audit:run --format=markdown > audit.md
```

Sous Windows, `vue:build` échoue sur la syntaxe `FORCE_COLOR=1` : récupérer la commande avec `php console vue:build Audit --print-build-command`, retirer le préfixe `cd ... &&`, remplacer les antislashs par des slashs et l'exécuter dans Git Bash.

## Avant de commit / release

- `PremiumLeakTest` et les autres tests unitaires passent, ESLint passe.
- Le plugin s'active sans erreur (`php console plugin:activate Audit`), la page `?module=Audit` s'affiche sans erreur console et `audit:run` tourne sans exception.
- Avec AuditPremium actif : pas de menu Audit, `?module=Audit` redirige vers AuditPremium, `audit:run` est celui d'AuditPremium.
- Bundle Vite reconstruit juste avant de taguer, `plugin.json` et `CHANGELOG.md` à jour.
- Documentation Marketplace (`README.md`, `docs/index.md`, `docs/faq.md`) à jour, liens premium avec les paramètres UTM `utm_medium=plugin_audit_documentation`.
- Aucun secret (mots de passe, salts, tokens) dans les exports.
