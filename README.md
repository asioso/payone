#DooFinder Bundle



##Installation

###composer
in your composer.json file add the following repo under your

```json
"repositories": [
    {
      "type": "vcs",
      "url":  "git@bitbucket.org:asioso/pimcore-payone-module.git"
    }
  ],
``` 

after that make sure you have access to the repo and added your ssh key to your bitbucket account.
test if composer can find the package.
```
composer search asioso

>>>>>
asioso/pimcore-payone-module A bundle to help with payone  

```

add the bundle to composer.json with
```
composer require asioso/pimcore-payone-module:dev-master

```

