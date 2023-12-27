# Projet BileMo

## CONTEXT

This project is about building an API for "BileMo", which is a website that references mobiles phones, and allow clients to check the products aswell as adding and deleting users that linked to them. I built the API using Symfony.

## INSTALLATION

To clone the project run the following command: 
```
git clone https://github.com/HyacineAlnuma/PHP-P7.git
```

To install the dependencies of the project run the following command:
```
composer install
```

### Environment variables

Create a .env.local file at the root of the project which is a copy of the .env file where you update the following variables with your own configuration:

JWT_PASSPHRASE


### Data

To load the fixtures run the following command:
```
php bin/console doctrine:fixtures:load
```

You can now check the documentation and try the API via https://localhost:8000/api/doc.
