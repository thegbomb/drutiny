<?php

namespace DrutinyTests;

use Drutiny\Target\TargetInterface;
use Drutiny\Entity\EventDispatchedDataBag;
use Drutiny\Target\Service\ExecutionService;
use Drutiny\Target\Service\LocalService;
use Prophecy\Prophet;
use Prophecy\Argument;
use PHPUnit\Framework\TestCase;

class TargetTest extends KernelTestCase {


  protected function setUp(): void
  {
      parent::setup();
      $this->prophet = new \Prophecy\Prophet;
  }

  protected function tearDown(): void
  {
      $this->prophet->checkPredictions();
  }

  // public function testNullTarget()
  // {
  //     //$target = $this->prophet->prophesize('Drutiny\Target');
  //     $target = $this->container->get('target.none');
  //     $this->assertInstanceOf(\Drutiny\Target\NullTarget::class, $target);
  //     $this->runStandardTests($target);
  // }

  protected function runStandardTests(TargetInterface $target, $uri = 'https://mysite.com/')
  {
      $target['test.foo'] = 'bar';
      $this->assertEquals($target['test.foo'], 'bar');
      $this->assertEquals($target->getProperty('test.foo'), 'bar');
      $this->assertInstanceOf(EventDispatchedDataBag::class, $target['test']);

      $this->assertInstanceOf(TargetInterface::class, $target);
      $this->assertEquals($target->getUri(), $uri);

      $this->assertInstanceOf(ExecutionService::class, $target['service.exec']);
      $this->assertSame($target['service.exec'], $target->getService('exec'));
  }

  public function testDrushTarget()
  {
    $prophecy = $this->prophet->prophesize('Drutiny\Target\Service\LocalService');

    $prophecy->replacePlaceholders(Argument::type('string'))->will(function ($args) {
      return $args[0];
    });

    $prophecy->run(Argument::type('string'), Argument::any(), Argument::any())->will(function ($args) {
      preg_match('/echo ([^ ]+) /', $args[0], $matches);
      $cmd = preg_match('/echo ([^ ]+) /', $args[0], $matches) ? base64_decode($matches[1]) : $args[0];

      switch (true) {
        case strpos($cmd, 'which drush') !== false:
          $value = '/var/www/html/app.dev/vendor/drush/drush/drush';
          break;

        case strpos($cmd, 'drush status') !== false:
          $value = '{"drupal-version":"8.9.18","uri":"http://env1.app.com","db-driver":"mysql","db-hostname":"staging-123","db-port":3306,"db-username":"appuser","db-name":"appdev","db-status":"Connected","bootstrap":"Successful","theme":"app_claro","admin-theme":"claro","php-bin":"/usr/local/php7.4/bin/php","php-conf":{"/usr/local/php7.4/etc/cli/php.ini":"/usr/local/php7.4/etc/cli/php.ini"},"php-os":"Linux","drush-script":"/mnt/www/html/appdev/vendor/drush/drush/drush","drush-version":"10.6.0","drush-temp":"/mnt/tmp/appdev","drush-conf":["/etc/drush/drush.yml","/mnt/www/html/app.dev/vendor/drush/drush/drush.yml","/mnt/www/html/app.dev/drush/drush.yml"],"install-profile":"default","root":"/mnt/www/html/app.dev/docroot","site":"sites/default","files":"sites/default/files","private":"/mnt/files/app.dev/sites/default/files-private","temp":"/mnt/tmp/appdev"}';
          break;

        case strpos($cmd, 'drush site:alias') !== false:
          $value = '{"app.dev":{"root":"/var/www/html/app.dev/docroot","uri":"dev1.app.com","path-aliases":{"%drush-script":"drush8"},"dev.livedev":{"parent":"@app.dev","root":"/mnt/gfs/app.dev/livedev/docroot"},"remote-host":"appdev.ssh.app.com","remote-user":"app.dev"}}';
          break;
        case strpos($cmd, 'php -v') !== false:
          $value = '7.4.28';
          break;
        default:
          throw new \Exception("Unforseen: {$args[0]}.");
      }
      return is_callable($args[1]) ? $args[1]($value) : $value;
    });

    $prophecy->setTarget(Argument::any())->will(function () use ($prophecy) {
      return $prophecy->reveal();
    });
    $prophecy->hasEnvVar('DRUSH_ROOT')->willReturn(true);

    $local = $prophecy->reveal();

    // Load without service container so we can use our prophecy.
    $target = new \Drutiny\Target\DrushTarget(
      new ExecutionService($local),
      $this->container->get('logger'),
      $this->container->get('Drutiny\Entity\EventDispatchedDataBag')
    );
    $this->assertInstanceOf(\Drutiny\Target\DrushTarget::class, $target);
    $target->parse('@app.env', 'https://env.app.com');
    $this->runStandardTests($target, 'https://env.app.com');

    $this->assertEquals($target['drush.drupal-version'], '8.9.18');
    $this->assertEquals($target->getUri(), 'https://env.app.com');

    $target = new \Drutiny\Target\DrushTarget(
      new ExecutionService($local),
      $this->container->get('logger'),
      $this->container->get('Drutiny\Entity\EventDispatchedDataBag')
    );
    $target->parse('@app.env');
    $this->assertEquals($target->getUri(), 'dev1.app.com');

  }


  public function testDdevTarget()
  {
    $prophecy = $this->prophet->prophesize('Drutiny\Target\Service\LocalService');

    $prophecy->replacePlaceholders(Argument::type('string'))->will(function ($args) {
      return $args[0];
    });

    // Catch unforseen argument calls.
    $prophecy->run(Argument::type('string'), Argument::type('callable'))->will(function ($args) {
      throw new \Exception("Unforseen: {$args[0]}.");
    });

    $prophecy->run(Argument::containingString('ddev describe'), Argument::type('callable'), Argument::any())->will(function ($args) {
      $response = '{"level":"info","msg":"┌───────────────────────────────────────────────────────────────────────────┐\n│ Project: app_ddev ~/Sandbox/drupal https://app_ddev.ddev.site                 │\n├────────────┬──────┬──────────────────────────────────┬────────────────────┤\n│ SERVICE    │ STAT │ URL/PORT                         │ INFO               │\n├────────────┼──────┼──────────────────────────────────┼────────────────────┤\n│ web        │ \u001b[32mOK\u001b[0m   │ https://app_ddev.ddev.site           │ drupal9 PHP7.4     │\n│            │      │ InDocker: ddev-app_ddev-web:443,80,8 │ nginx-fpm          │\n│            │      │ 025                              │ docroot:\'docroot\'  │\n│            │      │ Host: localhost:49152,65535      │                    │\n├────────────┼──────┼──────────────────────────────────┼────────────────────┤\n│ db         │ \u001b[32mOK\u001b[0m   │ InDocker: ddev-app_ddev-db:3306      │ MariaDB 10.3       │\n│            │      │ Host: localhost:65533            │ User/Pass: \'db/db\' │\n│            │      │                                  │ or \'root/root\'     │\n├────────────┼──────┼──────────────────────────────────┼────────────────────┤\n│ PHPMyAdmin │ \u001b[32mOK\u001b[0m   │ https://app_ddev.ddev.site:8037      │                    │\n│            │      │ InDocker: ddev-app_ddev-dba:80       │                    │\n│            │      │ `ddev launch -p`                 │                    │\n├────────────┼──────┼──────────────────────────────────┼────────────────────┤\n│ Mailhog    │      │ MailHog: https://app_ddev.ddev.site: │                    │\n│            │      │ 8026                             │                    │\n│            │      │ `ddev launch -m`                 │                    │\n├────────────┼──────┼──────────────────────────────────┼────────────────────┤\n│ All URLs   │      │ https://app_ddev.ddev.site,          │                    │\n│            │      │ https://127.0.0.1:49152,         │                    │\n│            │      │ http://app_ddev.ddev.site,           │                    │\n│            │      │ http://127.0.0.1:65535           │                    │\n└────────────┴──────┴──────────────────────────────────┴────────────────────┘\n","raw":{"approot":"/Users/user.name/Sandbox/drupal","database_type":"mariadb","dbaimg":"phpmyadmin:5","dbimg":"drud/ddev-dbserver-mariadb-10.3:v1.18.0","dbinfo":{"database_type":"mariadb","dbPort":"3306","dbname":"db","host":"ddev-app_ddev-db","mariadb_version":"10.3","password":"db","published_port":65533,"username":"db"},"docroot":"docroot","fail_on_hook_fail":false,"hostname":"app_ddev.ddev.site","hostnames":["app_ddev.ddev.site"],"httpURLs":["http://app_ddev.ddev.site","http://127.0.0.1:65535"],"httpsURLs":["https://app_ddev.ddev.site","https://127.0.0.1:49152"],"httpsurl":"https://app_ddev.ddev.site","httpurl":"http://app_ddev.ddev.site","mailhog_https_url":"https://app_ddev.ddev.site:8026","mailhog_url":"http://app_ddev.ddev.site:8025","mariadb_version":"10.3","mutagen_enabled":false,"name":"app_ddev","nfs_mount_enabled":false,"php_version":"7.4","phpmyadmin_https_url":"https://app_ddev.ddev.site:8037","phpmyadmin_url":"http://app_ddev.ddev.site:8036","primary_url":"https://app_ddev.ddev.site","router_disabled":false,"router_http_port":"80","router_https_port":"443","router_status":"healthy","router_status_log":"container was previously healthy, so sleeping 59 seconds before continuing healthcheck...  nginx config valid:OK  ddev nginx config:generated nginx healthcheck endpoint:OK ddev-router is healthy with 3 upstreams","services":{"db":{"exposed_ports":"3306","full_name":"ddev-app_ddev-db","host_ports":"65533","image":"drud/ddev-dbserver-mariadb-10.3:v1.18.0","status":"running"},"dba":{"exposed_ports":"80","full_name":"ddev-app_ddev-dba","host_ports":"","http_url":"http://app_ddev.ddev.site:8036","https_url":"https://app_ddev.ddev.site:8037","image":"phpmyadmin:5","status":"running"},"web":{"exposed_ports":"443,80,8025","full_name":"ddev-app_ddev-web","host_http_url":"http://127.0.0.1:65535","host_https_url":"https://127.0.0.1:49152","host_ports":"49152,65535","http_url":"http://app_ddev.ddev.site","https_url":"https://app_ddev.ddev.site","image":"drud/ddev-webserver:v1.18.0","status":"running"}},"shortroot":"~/Sandbox/drupal","ssh_agent_status":"healthy","status":"running","type":"drupal9","urls":["https://app_ddev.ddev.site","https://127.0.0.1:49152","http://app_ddev.ddev.site","http://127.0.0.1:65535"],"webimg":"drud/ddev-webserver:v1.18.0","webserver_type":"nginx-fpm","xdebug_enabled":false},"time":"2022-04-11T08:46:07+12:00"}';
        return $args[1]($response);
    });

    $prophecy->run(Argument::type('string'), Argument::any(), Argument::any())->will(function ($args) {
      if (strpos($args[0], 'docker exec') === false) {
        throw new \Exception("Not a docker command: {$args[0]}.");
      }
      $cmd = preg_match('/echo ([^ ]+) /', $args[0], $matches) ? base64_decode($matches[1]) : $args[0];

      switch (true) {
        case strpos($cmd, 'which drush') !== false:
          $value = '/var/www/html/app.dev/vendor/drush/drush/drush';
          break;

        case strpos($cmd, 'drush status') !== false:
          $value = '{"drupal-version":"8.9.18","uri":"http://env1.app.com","db-driver":"mysql","db-hostname":"staging-123","db-port":3306,"db-username":"appuser","db-name":"appdev","db-status":"Connected","bootstrap":"Successful","theme":"app_claro","admin-theme":"claro","php-bin":"/usr/local/php7.4/bin/php","php-conf":{"/usr/local/php7.4/etc/cli/php.ini":"/usr/local/php7.4/etc/cli/php.ini"},"php-os":"Linux","drush-script":"/mnt/www/html/appdev/vendor/drush/drush/drush","drush-version":"10.6.0","drush-temp":"/mnt/tmp/appdev","drush-conf":["/etc/drush/drush.yml","/mnt/www/html/app.dev/vendor/drush/drush/drush.yml","/mnt/www/html/app.dev/drush/drush.yml"],"install-profile":"default","root":"/mnt/www/html/app.dev/docroot","site":"sites/default","files":"sites/default/files","private":"/mnt/files/app.dev/sites/default/files-private","temp":"/mnt/tmp/appdev"}';
          break;

        case strpos($cmd, 'drush site:alias') !== false:
          $value = '{"app.dev":{"root":"/var/www/html/app.dev/docroot","uri":"dev1.app.com","path-aliases":{"%drush-script":"drush8"},"dev.livedev":{"parent":"@app.dev","root":"/mnt/gfs/app.dev/livedev/docroot"},"remote-host":"appdev.ssh.app.com","remote-user":"app.dev"}}';
          break;
        case strpos($cmd, 'php -v') !== false:
          $value = '7.4.28';
          break;
        default:
          throw new \Exception("Unforseen: {$args[0]}.");
      }
      return is_callable($args[1]) ? $args[1]($value) : $value;
    });

    $prophecy->setTarget(Argument::any())->will(function () use ($prophecy) {
      return $prophecy->reveal();
    });
    $prophecy->hasEnvVar('DRUSH_ROOT')->willReturn(true);


    $local = $prophecy->reveal();

    // Load without service container so we can use our prophecy.
    $target = new \Drutiny\Target\DdevTarget(
      new ExecutionService($local),
      $this->container->get('logger'),
      $this->container->get('Drutiny\Entity\EventDispatchedDataBag')
    );
    $this->assertInstanceOf(\Drutiny\Target\DdevTarget::class, $target);
    $target->parse('ddev_app', 'https://env.app.com');

    $this->assertEquals($target['drush.drupal-version'], '8.9.18');
    $this->assertEquals($target->getUri(), 'https://env.app.com');
  }

  public function testLandoTarget()
  {
    $prophecy = $this->prophet->prophesize('Drutiny\Target\Service\LocalService');

    $prophecy->replacePlaceholders(Argument::type('string'))->will(function ($args) {
      return $args[0];
    });

    // Catch unforseen argument calls.
    $prophecy->run(Argument::type('string'), Argument::type('callable'))->will(function ($args) {
      throw new \Exception("Unforseen: {$args[0]}.");
    });

    $prophecy->run(Argument::containingString('lando list --format=json'), Argument::type('callable'), Argument::any())->will(function ($args) {
      $response = '[{"service":"database","name":"appenv_database_1","app":"appenv","src":["/Users/user.name/Sandbox/appenv/site/.lando.yml"],"kind":"app","status":"Up 47 seconds"},{"service":"appserver","name":"appenv_appserver_1","app":"appenv","src":["/Users/user.name/Sandbox/appenv/site/.lando.yml"],"kind":"app","status":"Up 47 seconds"},{"service":"proxy","name":"landoproxyhyperion5000gandalfedition_proxy_1","app":"_global_","src":"unknown","kind":"service","status":"Up 2 minutes"}]';
      return $args[1]($response);
    });

    $prophecy->run(Argument::containingString('lando info --format=json'), Argument::type('callable'), Argument::any())->will(function ($args) {
      $response = '[{"service":"appserver","urls":["https://localhost:57856","http://localhost:57857","http://contenta-cms.lndo.site:8000/","https://contenta-cms.lndo.site:444/"],"type":"php","healthy":true,"via":"apache","webroot":"web","config":{"php":"/Users/joshua.waihi/.lando/config/drupal9/php.ini"},"version":"7.4","meUser":"www-data","hasCerts":true,"hostnames":["appserver.contentacms.internal"]},{"service":"database","urls":[],"type":"mysql","healthy":true,"internal_connection":{"host":"database","port":"3306"},"external_connection":{"host":"127.0.0.1","port":"57855"},"healthcheck":"bash -c \"[ -f /bitnami/mysql/.mysql_initialized ]\"","creds":{"database":"drupal9","password":"drupal9","user":"drupal9"},"config":{"database":"/Users/joshua.waihi/.lando/config/drupal9/mysql.cnf"},"version":"5.7","meUser":"www-data","hasCerts":false,"hostnames":["database.contentacms.internal"]}]';
      return $args[1]($response);
    });

    $prophecy->run(Argument::type('string'), Argument::any(), Argument::any())->will(function ($args) {
      if (strpos($args[0], 'docker exec') === false) {
        throw new \Exception("Not a docker command: {$args[0]}.");
      }
      $cmd = preg_match('/echo ([^ ]+) /', $args[0], $matches) ? base64_decode($matches[1]) : $args[0];

      switch (true) {
        case strpos($cmd, 'which drush') !== false:
          $value = '/var/www/html/app.dev/vendor/drush/drush/drush';
          break;

        case strpos($cmd, 'drush status') !== false:
          $value = '{"drupal-version":"8.9.18","uri":"http://env1.app.com","db-driver":"mysql","db-hostname":"staging-123","db-port":3306,"db-username":"appuser","db-name":"appdev","db-status":"Connected","bootstrap":"Successful","theme":"app_claro","admin-theme":"claro","php-bin":"/usr/local/php7.4/bin/php","php-conf":{"/usr/local/php7.4/etc/cli/php.ini":"/usr/local/php7.4/etc/cli/php.ini"},"php-os":"Linux","drush-script":"/mnt/www/html/appdev/vendor/drush/drush/drush","drush-version":"10.6.0","drush-temp":"/mnt/tmp/appdev","drush-conf":["/etc/drush/drush.yml","/mnt/www/html/app.dev/vendor/drush/drush/drush.yml","/mnt/www/html/app.dev/drush/drush.yml"],"install-profile":"default","root":"/mnt/www/html/app.dev/docroot","site":"sites/default","files":"sites/default/files","private":"/mnt/files/app.dev/sites/default/files-private","temp":"/mnt/tmp/appdev"}';
          break;

        case strpos($cmd, 'drush site:alias') !== false:
          $value = '{"app.dev":{"root":"/var/www/html/app.dev/docroot","uri":"dev1.app.com","path-aliases":{"%drush-script":"drush8"},"dev.livedev":{"parent":"@app.dev","root":"/mnt/gfs/app.dev/livedev/docroot"},"remote-host":"appdev.ssh.app.com","remote-user":"app.dev"}}';
          break;
        case strpos($cmd, 'php -v') !== false:
          $value = '7.4.28';
          break;
        default:
          throw new \Exception("Unforseen: {$args[0]}.");
      }
      return is_callable($args[1]) ? $args[1]($value) : $value;
    });

    $prophecy->setTarget(Argument::any())->will(function () use ($prophecy) {
      return $prophecy->reveal();
    });
    $prophecy->hasEnvVar('DRUSH_ROOT')->willReturn(true);


    $local = $prophecy->reveal();

    // Load without service container so we can use our prophecy.
    $target = new \Drutiny\Target\LandoTarget(
      new ExecutionService($local),
      $this->container->get('logger'),
      $this->container->get('Drutiny\Entity\EventDispatchedDataBag')
    );
    $this->assertInstanceOf(\Drutiny\Target\LandoTarget::class, $target);
    $target->parse('appenv', 'https://env.app.com');

    $this->assertEquals($target['drush.drupal-version'], '8.9.18');
    $this->assertEquals($target->getUri(), 'https://env.app.com');
  }
}
