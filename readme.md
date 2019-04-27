# Laravel Service Container


>An IoC Container is a convenience Mechanism for achieving Dependency Injection -Taylor Otwell



Laravel is one of the most popular, highly used, open-source modern web application framework. It provides unique features like Eloquent ORM, Query builder ,Homestead which are the modern features, only present in Laravel.

I like Laravel because of its unique architectural design.Behind the scene Laravel uses different design pattern such as Singleton, Factory, Builder, Facade, Strategy, Provider, Proxy etc. So when my knowledge is increasing, I am finding its beauty. Laravel makes developer’s life more easy and removes boringness.

Learning code with Laravel is not just about learning to use the different classes but also learning the philosophy of Laravel, its elegance and its beautiful syntax. An important part of Laravel’s philosophy is the IoC container or Service Container. Understanding and using the IoC container is a crucial part in mastering our craft, as it is the core part of a Laravel application.

Service Container is a powerful tool for managing class dependencies and performing dependency injection. It has the power to automatically resolve classes without configuration. Here I will try to discuss why we need it and How it works.

If at first we know Dependency Inversion Principle it will help us to understand why we need Service Container. So in the beginning I will discuss Dependency Inversion Principle.

The principle states:

>High-level modules should not depend on low-level modules. Both should depend on abstractions.

> Abstractions should not depend on details. Details should depend on abstractions.

Or simply : Depend on Abstractions not on concretions

```php
class MySQLConnection
{
   /**
   * db connection
   */
   public function connect()
   {
      var_dump(‘MYSQL Connection’);
   }
}

class PasswordReminder
{    
    /**
     * @var MySQLConnection
     */
     private $dbConnection;
    public function __construct(MySQLConnection $dbConnection) 
    {
      $this->dbConnection = $dbConnection;
    }
}
```


There’s a common misunderstanding that dependency inversion is simply another way to say dependency injection. However, the two are not the same.In the above code Inspite of Injecting MySQLConnection class in PasswordReminder class but it is depends on MySQLConnection.

High-level module PasswordReminder should not depend on low-level module MySQLConnection.

If we want to change connection from MySQLConnection to MongoDBConnection, we have to change hard coded constructor injection in PasswordReminder class.

PasswordReminder class should depend upon on Abstractions not on concretions. But How can we do it ? Please see the following example :

```php
interface ConnectionInterface
{
   public function connect();
}
class DbConnection implements ConnectionInterface
{
 /**
  * db connection
  */
 public function connect()
 {
   var_dump(‘MYSQL Connection’);
 }
}
class PasswordReminder
{
    /**
    * @var DBConnection
    */
    private $dbConnection;
    public function __construct(ConnectionInterface $dbConnection)
    {
      $this->dbConnection = $dbConnection;
    }
}

```

In the above code we want to change connection from MySQLConnection to MongoDBConnection, we no need to change constructor injection in PasswordReminder class.Because here PasswordReminder class depends upon on Abstractions not on concretions.

If your concept is not clear about interface then you can read this [doc](https://medium.com/@NahidulHasan/understanding-use-of-interface-and-abstract-class-9a82f5f15837) . This doc will help you to understand Dependency Inversion Principle, IoC container etc clearly.

Now I will discuss what happens in IoC container. we can simply say that IoC container is a Container that contains Inversion of Control (dependencies of a class).

OrderRepositoryInterface :

```php
namespace App\Repositories;
interface OrderRepositoryInterface 
{
   public function getAll();
}

```

DbOrderRepository class:

```php
namespace App\Repositories;
class DbOrderRepository implements OrderRepositoryInterface
{
 
  function getAll()
  {
    return 'Getting all from mysql';
  }
}

```

OrdersController class:

```php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Http\Requests;
use App\Repositories\OrderRepositoryInterface;
class OrdersController extends Controller
{
    protected $order;
   function __construct(OrderRepositoryInterface $order)
   {
     $this->order = $order;
   }
    
   public function index()
   {
     dd($this->order->getAll());
     return View::make(orders.index);
   }
}

```

Routing:

```php
Route::resource('orders', 'OrdersController');

```

Now if you hit browser using this url http://localhost:8000/orders

Yow will get this error .Because container is trying to instantiate the interface. We can fix that by creating a specific binding for our interface.


BindingResolutionException in Container.php line 748:
Target [App\Repositories\OrderRepositoryInterface] is not instantiable while building [App\Http\Controllers\OrdersController].

Just adding this line code in route page we can solve it.

```php
App::bind('App\Repositories\OrderRepositoryInterface', 'App\Repositories\DbOrderRepository');

```

Now if you hit browser you will get :

```
"Getting all from mysql"

```


Here, Need to mention that, We should not resolve app bind in route page. Here I have added  only for example purpose. 
In our professional project we should have resolve app binding issue in ``AppServiceProvider`` class ``register``  method 
in the following way:

```
$this->app->bind('App\Repositories\OrderRepositoryInterface', 'App\Repositories\DbOrderRepository');
```


we can define a container class in following way:

```php
class SimpleContainer
 {
    protected static $container = [];
    public static function bind($name, Callable $resolver)
    {   
        static::$container[$name] = $resolver;
    }
    public static function make($name)
    {
      if(isset(static::$container[$name])){
        $resolver = static::$container[$name] ;
        return $resolver();
    }
    throw new Exception("Binding does not exist in containeer");
   }
}

```

Here I will try to show that how simple container resolves dependency

```php

class LogToDatabase 
{
    public function execute($message)
    {
       var_dump('log the message to a database :'.$message);
    }
}
class UsersController {
    
    protected $logger;
    
    public function __construct(LogToDatabase $logger)
    {
        $this->logger = $logger;
    }
    
    public function show()
    {
      $user = 'JohnDoe';
      $this->logger->execute($user);
    }
}

```

Here bind dependency.

```php

SimpleContainer::bind('Foo', function()
 {
   return new UsersController(new LogToDatabase);
 });
$foo = SimpleContainer::make('Foo');
print_r($foo->show());

```

Output :

```
string(36) "Log the messages to a file : JohnDoe"

```

Laravel’s container code :

```php
public function bind($abstract, $concrete = null, $shared = false)
    {
        $abstract = $this->normalize($abstract);
        $concrete = $this->normalize($concrete);
        if (is_array($abstract)) {
           list($abstract, $alias) = $this->extractAlias($abstract);
           $this->alias($abstract, $alias);
        }
        $this->dropStaleInstances($abstract);
        if (is_null($concrete)) {
            $concrete = $abstract;
        }

        if (! $concrete instanceof Closure) {
            $concrete = $this->getClosure($abstract, $concrete);
        }
        
        $this->bindings[$abstract] = compact('concrete', 'shared');

        if ($this->resolved($abstract)) {
            $this->rebound($abstract);
        }
    }
public function make($abstract, array $parameters = [])
    {
        $abstract = $this->getAlias($this->normalize($abstract));
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }
      $concrete = $this->getConcrete($abstract);
        if ($this->isBuildable($concrete, $abstract)) {
            $object = $this->build($concrete, $parameters);
        } else {
            $object = $this->make($concrete, $parameters);
        }

        foreach ($this->getExtenders($abstract) as $extender) {
            $object = $extender($object, $this);
        }
        if ($this->isShared($abstract)) {
            $this->instances[$abstract] = $object;
        }
       $this->fireResolvingCallbacks($abstract, $object);
       $this->resolved[$abstract] = true;
       return $object;
    }
public function build($concrete, array $parameters = [])
    {
        
        if ($concrete instanceof Closure) {
            return $concrete($this, $parameters);
        }
       $reflector = new ReflectionClass($concrete);
        if (! $reflector->isInstantiable()) {
            if (! empty($this->buildStack)) {
                $previous = implode(', ', $this->buildStack);
        $message = "Target [$concrete] is not instantiable while building [$previous].";
            } else {
                $message = "Target [$concrete] is not instantiable.";
            }
          throw new BindingResolutionException($message);
        }
         $this->buildStack[] = $concrete;
         $constructor = $reflector->getConstructor();
        if (is_null($constructor)) {
            array_pop($this->buildStack);
           return new $concrete;
        }
        $dependencies = $constructor->getParameters();
        $parameters = $this->keyParametersByArgument(
            $dependencies, $parameters
        );
     $instances = $this->getDependencies($dependencies,$parameters);
     array_pop($this->buildStack);
     return $reflector->newInstanceArgs($instances);
    }


```

If you want to know more details all method about container then you can see 

vendor/laravel/framwork/src/Illuminate/Container/Container.php

Simple Bindings

```php

$this->app->bind('HelpSpot\API', function ($app) {
    return new HelpSpot\API($app->make('HttpClient'));
});

```

Binding A Singleton

The singleton method binds a class or interface into the container that should only be resolved one time.

```php
$this->app->singleton('HelpSpot\API', function ($app) {
    return new HelpSpot\API($app->make('HttpClient'));
});



```


Binding Instances

You may also bind an existing object instance into the container using the instance method. The given instance will always be returned on subsequent calls into the container:

```php

$api = new HelpSpot\API(new HttpClient);

$this->app->instance('HelpSpot\API', $api);

```

If there is no binding, PHP’s Reflection class is used to resolve the instance and dependencies.

You can learn more about it by reading the [docs](https://laravel.com/docs/5.6/container)

I have published this article in the medium. if you’d like to read from the medium blog site, please go to this [link](https://medium.com/@NahidulHasan/laravel-ioc-container-why-we-need-it-and-how-it-works-a603d4cef10f)

Thank you for reading.
