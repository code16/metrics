# Metrics
[![Latest Stable Version](https://poser.pugx.org/code16/metrics/v/stable)](https://packagist.org/packages/code16/metrics)
[![Total Downloads](https://poser.pugx.org/code16/metrics/downloads)](https://packagist.org/packages/code16/metrics)
[![License](https://poser.pugx.org/code16/metrics/license)](https://packagist.org/packages/code16/metrics)
[![Build](https://travis-ci.org/code16/metrics.svg?branch=master)](https://packagist.org/packages/code16/metrics)

Metrics is a Laravel 5 package aimed at logging visitor's usage of your web application and provides an ensemble of classes and functionnality to help you generate statistics out of it. 

It is compliant with `EU` regulations regarding privacy, and `GDPR-Ready`. 

## Installation

You can install the metrics package by typing the following command :

```
    composer require dvlpp/metrics:dev-master
```

## Configuration

THe package ships with a set of ready-to-user `Analyzers` that are included in default's config file. If you want to customize it, just publish using the appropriate artisan command : 

```
php artisan vendor:publish --provider="Code16\Metrics\MetricServiceProvider" --tag="config"
```

## Tracking

`Metrics`works by placing a tracking cookie on the user's device, which will be linked to a specific user_id when a user is logged in, so there will be a unique identifier for a single user, whichever device he's using, and the cookie will stay on after a user has logged out, meaning that you can reconstruct a user's sequence before, or after he was logged into the application. 

By default, `Metrics` will log any route accessed within the application, with the exception of when the user has a `Do Not Track` header set to true. If you do not wish a specific route or group of route to be logged, you can use the included `NoTrackingMiddleware`, or add a filter in config. 

## Actions

`Actions` are an important concept in `Metrics`. They give you the opportunity to highlight and gather data for further statistic analyzis on some important part of your application. They are very similar to events in how they work, and they are serialized in the `metrics_visits`table.

For example, you can create an action class for everytime a user add a product to his cart :  

```
    use App\Product;
    use Code16\Metrics\Action;

    class AddToCart extends Action {

        public $productId;

        public function __construct(Product $product)
        {
            $this->productId = $product;
        }

    }
```

Then, you can attach the action to the current Visit object, by calling the following method on the manager class : 

```
    app(Dvlpp\Metrics\Manager::class)->addAction(new AddToCart($product));
```

Alternatively, you can use the helper function :

```
    metrics_action(new AddTOCart($product))
```

## Analyzers & Consoliders

On top of logging your user's visits into a table, `Metrics` comes with a little "framework" which aims to help you extract statistics from these raw informations. It come out of the box with some basic statistics functions that will for example count the number of unique visitors or analysis the repartition of your users by browser or user agent. 

There are two distinct operations that `Metrics` handles : 

`Analyze` : This operation will produce a statistic array from raw `Visit` records. Basically metrics passes all rows from the `metrics_visit` table and listen and store statistics data that is calculated from these raw visit log. 

`Consolidate` : This operation takes several results from several smaller time periods into a larger one. 

Raw data are analyzed and consolidated into four incremental time periods : 

- Hour
- Day
- Month
- Year

For some example on how to write custom analyzers/consoliders for your application, you can look into the classes which are included in the package (Code16\Metrics\Analyzers).


## Updating statistics

To calculate and update the statistics, simply run `artisan metrics:update` at any time. Doing so will calculate statistics for all complete period (last hour, last day, last month..), that haven't been calculated yet. TIP : If you implement new `Analyzers` during the application lifetime, and you want to calculate statistics on previous period, simply truncate `metric_metrics` table and run another update. 