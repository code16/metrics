# Metrics

Metrics is a Laravel 5 package aimed at logging visitor's usage of your web application and provides an ensemble of classes and functionnality to help you generate statistics out of it. 

It makes a heavy usage of middleware and therefore it can sit on top of an existing application quite easily. 

## Installation

You can install the metrics package by typing the following command :

```
    composer require dvlpp/metrics:dev-master
```

Then, you'll need to run the migration command to create the required database tables : 

```
    php artisan metrics:migrate
```

## Configuration



## Tracking

`Metrics`works by placing a tracking cookie on the user's device. The cookie itself is a random string (TODO : function that can be customized), that will be linked to a specific user_id when a user is logged in, so there will be a unique identifier for a single user, whichever device he's using, and the cookie will stay on after a user has logged out, meaning that you can reconstruct a user's sequence before, or after he was logged into the application. 

By default, `Metrics` will log any route accessed within the application, with the exception of when the user has a `Do Not Track` header set to true. If you do not wish a specific route or group of route to be logged, you can use the included `NoTrackingMiddleware`.

## Actions

`Actions` are an important concept in `Metrics`. They give you the opportunity to highlight and gather data for further statistic analyzis on some important part of your application. They are very similar to events in how they work, and they are serialized in the `metrics_visits`table.

For example, you can create an action class for everytime a user add a product to his cart :  

```
    use App\Product;
    use Dvlpp\Metrics\Action;

    class AddToCart extends Action {

        public $productId;

        public function __construct(Product $product)
        {
            $this->productId = $product;
        }

    }
```

(TODO : Uses of serializes models)

Then, you can attach the action to the current Visit object, by calling the following method on the manager class : 

```
    app(Dvlpp\Metrics\Manager::class)->addAction(new AddToCart($product));
```

You can also use the helper function :

```
    metrics_action(new AddTOCart($product))
```




## Generating statistics from your log

A note about time intervals= TimeInterval class

## Analyzers


## Consoliders


## Updating statistics
