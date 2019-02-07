# Requirements

Docker

# Install

(install script working on ubuntu 18.x)
```bash
apt update
apt-get install docker -y
apt-get install docker.io -y
apt-get install docker-compose -y
apt-get install nginx -y
sysctl -w vm.max_map_count=262144 #<--very important if you run on a VM for elastic to work!

cd /root
mkdir api
cd api
git clone https://repo/ .
git checkout staging

vi config/app.php #change APP_URL
vi .env #change APP_URL


chmod -R +x ./build-deploy/
docker-compose up -d
	#-> may have to run /etc/init.d/nginx stop incase port 80 already in use

docker-compose ps # <---check all running

docker container exec -it app bash
composer install
composer dumpautoload
head /etc/nginx/nginx.conf # to find out the web user (may be www-data)
chown -R nginx:nginx .
chmod -R 777 storage
chmod -Rf 777 /var/www/app/storage/logs
chmod -Rf 777 /var/www/app/storage/framework/views/
chmod -Rf 777 /var/www/app/storage/framework/sessions/
chmod -Rf 777 /var/www/app/storage/framework/cache/
sh ./build-deploy/first-run.sh
```

TEST YOUR SETUP IS WORKING:
* you can access http://localhost/dashboard
* you can login to the dashboard (admin PW is in seeders file: database/seeds/StaffUsersSeeder.php)
* goto business page--->result: there are items on the map (this comes from elastic)


# Run

To start the project, just run:

```bash
docker-compose up
```

If you want to start containers in background (as a daemon), add the `-d` flag:

```bash
docker-compose up -d
```

If this is your first run, then after containers are up and ready, run the next command to setup/install the project dependencies:

```bash
docker-compose run --rm app ./build-deploy/first-run.sh
```

# Stop

You can stop containers by typing `Cmd + C` on Mac or `Ctrl + C` on Windows/Linux. 

If you started the project in background, then run:

```bash
docker-compose stop
```
# Force Running Project

There are many unexpected problems to stop running project. ex. Low version Ubuntu, OpenVPN connect problem.
```bash
sudo service docker restart
docker-compose down --rmi=local -v -f
```
This command will stop docker and restart, also remove all docker containers.
And Follow above steps again. Important : 1.if you use vpn tool, disable it. 2. Maintain Memory 4GB: Run ElasticSearch docker Container.

# Cleanup

If you want to cleanup your Docker instances for a fresh start, run:

```bash
docker-compose down --rmi=local -v
```

This command will stop and delete the containers, local images and volumes.

# Updating external images

If you want to get latest versions of your external images, run:

```bash
docker-compose pull
```

# Queues

Once your containers are started, you can start the queue listeners when needed.

### Mailing Queue

```bash
docker-compose run --rm app php artisan queue:work sqs_mail
```

### SMS Queue

```bash
docker-compose run --rm app php artisan queue:work sqs_sms
```

# Tests

To run the test suite:
```bash
docker-compose run --rm app ./build-deploy/test.sh
```

# ElasticSearch Indexes

To regenerate the elastic search indexes:
```bash
docker-compose run --rm app php artisan elastic:setup-indexes
```

# Mac Set-up

```Docker Mac

    1. Install the docker in your mac

    2. After installing docker in your mac clone the repo (app) into your machine 

    3. Configure .env file and change mysql db_host to 127.0.0.1 and also change in docker.yaml file. mysql Environment to `MYSQL_HOST=127.0.0.1` and `app: aliases: - 127.0.0.1`

    4. Inside your app run `composer commands` to update all packages

    5. after that run make sure you have the righ permissions for that
            go to app->build-deploy
                run `sudo chmod  +x first_run.sh`
                run `sudo chmod  +x run.sh`
            next go to app->build-deploy->image
                run `sudo chmod  +x run.sh`

    6. Now run  `docker-compose build` and  inside your app

    7. Start Laravel server to access app

    If you use sequel pro for mysql use these credentials database: app, host: 127.0.0.1, username: root
    Now you are able to access the app `http://127.0.0.1:8000/dashboard:login` url

    ```
#################################################
#         SWAGGER USING DOCUMENTATION           #
#################################################

# Generate beautiful RESTful Laravel API documentation with Swagger
- Run: php artisan list  TO check swagger already integrated in application:
If swagger already integrated, it will show these items in artisan list
l5-swagger
  l5-swagger:generate        Regenerate docs
  l5-swagger:publish         Publish config, views

- Create an endpoint and automate the documentation:
In controller class, above method of controller which you want to make API documentation, add below lines in comment:
/**
     * @OA\Post/Get/Delete/Put(
     *     path="/api/v1/{method_route_url}",
     *     summary="Example summary.",
     *     @OA\Parameter(
     *         list all parameters for method
     *         required=true,
     *         in="query"
     *     ),
     *     @OA\Response(
     *         write response format here
     *     )
     * )
     */


- RUN: php artisan l5-swagger:generate to generate document

- API document should be here: {app_url}/api/documentation


#################################################
#         DEBUGGING								#
#################################################

################################################
# ISSUE INSTALLING `composer install`
################################################
  Problem 1
    - laravel/nova 1.0.x-dev requires babenkoivan/scout-elasticsearch-driver v3.8.2 -> satisfiable by babenkoivan/scout-elasticsearch-driver[v3.8.2].
    - laravel/nova dev-master requires babenkoivan/scout-elasticsearch-driver v3.8.2 -> satisfiable by babenkoivan/scout-elasticsearch-driver[v3.8.2].
    - Conclusion: don't install babenkoivan/scout-elasticsearch-driver v3.8.2
    - Installation request for laravel/nova * -> satisfiable by laravel/nova[1.0.x-dev, dev-master].

FIX:
remove the babenkoivan package from composer json - it is pulled in as a direct dependency of nova package automatically with a correct version
################################################


===================================
GENERAL DEBUG
===================================
tail -f storage/logs/laravel.log





#################################################
#         HELPFUL / NOTES						#
#################################################

===================================
#To run all of your outstanding migrations, execute the migrate Artisan command:
php artisan migrate
===================================
DOCS URL
/api/documentation

ENABLE: L5_SWAGGER_GENERATE_ALWAYS in: config/l5-swagger.php to always regenerate (NOT FOR PROD)
===================================
===================================
RESET KIBANA PW
===================================
docker container exec -it api bash
htpasswd /etc/nginx/domains.d/kibana.htpasswd kibana
===================================
RESET A PASSWORD
===================================
cd <your_larave_project_directory_path>
php artisan tinker
Psy Shell v0.4.4 (PHP 5.5.28 — cli) by Justin Hileman
>>>
$user = App\User::where('email', 'test@outlook.com')->first();
$user->password = Hash::make('thanhnd123');
$user->save();
===================================

===================================
IMAGES NOT LOADIN IN ADMIN?
config/app.php - change APP_URL
===================================


