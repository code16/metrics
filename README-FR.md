#Projet de Package `Metrics`

Ce document a pour but de réflechir à ce que pourrait être un package composer destiné à récolter des Metrics & statistique d'usage au sein d'une application Laravel. 

##Philosophie

L'idée est de créer une base simple, qui prend en charge les fonctionnalités de base que l'on attend d'un outil d'analyse de visites, et de fournir une `architecture object` ou l'application puisse se brancher pour générer des statistiques qui ont du sens dans son contexte. Dans cette idée de simplicité, le package s'articulerait autour de 2 tables :

- `visits` : stocke les données de façon brut. une ligne par requête.
- `metrics` : stocke les données consolidées, une ligne par période. 

##Récolte des données

Chaque requête à l'application est interceptée par défaut par un middleware global et une instance de la classe `Visit` est créée. Cette instance de `Visit` embarque les données de base de tracking (adresse IP, navigateur, refferer, etc..) et permet d'identifier un utilisateur de façon unique. Le middleware peut être désactivé sur certaine routes (backend...) par le biais d'un middleware `no-metrics`.

Si un utilisateur est loggué, son `user_id` est lié automatiquement à l'instance de `Visit`. Dans le même ordre d'idée, un `hook` permet de retro affecter d'éventuelles instances précédentes de `Visit` au moment ou un utilisateur s'identifie dans l'application.

L'instance de `Visit` reste en mémoire jusqu'a la fin de la requête, moment ou elle sera stockée en BDD. Ceci pour deux raisons : 

- L'application peut y attacher des données spécifiques au cours de la requete (voir Actions). 
- L'opération de stockage prend place après que la réponse est été envoyée au navigateur, afin d'avoir le minimum d'impact possible sur le temps de réponse.

##Données additionneles

Une application peut avoir besoin de récolter des données additionnelles à chaque requête (ex: l'ID du magasin selectionné sur un site de drive). Ces données seront stockées dans une propriété `custom` de l'instance `Visit`, sous forme d'un tableau associatif. 

##Actions

En addition des données récoltées systématiquement à chaque requête, les `actions` sont destinées à stocker des données importantes dans le contexte de l'application qui l'utilise. Techniquement, une `action` prend la forme d'une instance de classe qui sera rattachée à l'instance de `Visit` en cours. Exemple simple : 

```php

    use Dvlpp\Metrics\Action;

    class AddToCart extends Action {

        protected $productId;

        public function __construct($productId)
        {
            $this->productId = $productId;
        }

        public function getCaption()
        {
            $id = $this->productId;
            return "A ajouté l'article $id à son panier"; 
        }
    }

```

L'intérêt d'utiliser des actions est multiple :

- Plusieurs routes peuvent aboutir à une action semblable (ex: ajout d'un article au panier en passant par le site web ou l'appli mobile). L'utilisation d'actions simplifie dans ce cas la centralisation d'une même metrique.
- Permettre une consolidation personnalisée des données (voir `consolidation`)
- Reconstituer le chemin d'un utilisateur de façon 'verbose' (interface admin)

Les `actions` sont sérialisées dans le champs correspondant de l'instance de `Visit`. Il n'y a pas de limite au nombre d'actions par requête.

L'action peut évidemment embarquer plusieurs propriété. On peut par exemple imaginer que l'on veut connaitre pour chaque panier le nombre d'article et le montant total, afin d'éventuellement en extraire des moyennes. 


##Metrics Hors Requête  

Certaines statistiques ou données peuvent être interessante à analyser hors du contexte d'une requête. Par exemple il peut s'agir de récupérer le nombre de panier non-validés par des utilisateurs pour en extraire des tendances. 

Ces mesures pourraient intervenir au moment du cron de `consolidation`. 

(à approfondir)


##Analyze & Consolidation

L'`analyze` est l'opération de transformation qui permet de passer d'un nombre x de lignes dans `visits` à une seule ligne dans `metrics`. 

La `consolidation` est l'opération qui permet de passer d'un nombre x de lignes dans `metrics` à une seule ligne (ex: passage des 4 dernières semaines à un mois).

Le package se charge d'un certains nombre de consolidation standard (ex: % de navigateurs utilisés, pages les plus vues, etc...). 

L'application, de son côté, peut ajouter des classes d'analyze qui seront exécutées lors de ce processus. Par exemple, si l'ont reprend l'exemple des paniers sur un site d'ecommerce, on peut vouloir connaitre le TOP 10 des produits ajoutées au cours d'une période : 

 ```php

    use Dvlpp\Metrics\Analyzer;

    class TopProductAnalyzer extends Analyzer {

        public function analyze(Collection $visits)
        {
            // Filter visits with AddToCart actions

            // Compile informations

            // Return example
            return [
                '1' => [
                    'id' => 123,
                    'hits' => 10485,
                ],
                '2' => [
                    'id' => 567,
                    'hits' => 9875,
                ],
                [...]
            ];
        }

        // $metrics en entrée est un ensemble de tableaux retournés par la
        // méthode analyze() de la même classe. 
        public function cosolidate($metrics)
        {
            // Return example
            return [
                '1' => [
                    'id' => 123,
                    'hits' => 1000000,
                ],
                '2' => [
                    'id' => 567,
                    'hits' => 234585,
                ],
                [...]
            ];

        }

    }

```

##Stockage

// Repository

##Durée de vie des données

La durée de la conservation des enregistrements de la table `visits` doit pouvoir être configurée de manière indépendante de leur consolidation dans la table `metrics`, ceci afin de pouvoir reconstituer des chemins d'utilisateurs même après que les données soit consolidées.

##Visualisation

Pourrait s'inspirer en partie de [Spatie-Dashboard](https://github.com/spatie/dashboard.spatie.be). 
