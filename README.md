MySQLWrapper
============
TODO:
  - Étoffer le fichier d'exemple ;
  - Est-ce que les options de configuration sont vraiment utiles ou vaut-il mieux imposer le choix exceptions + tuple = tableau associatif ?
  - Ajouter ci-dessous des explications sur le support des requêtes préparées ;
  - Écrire une documentation plus précise. Dans l'état actuel du projet, **reportez vous à la documentation du code source**.


Chez certains hébergeurs gratuits (notament Free, pour ne pas le citer), nous sommes contraints d'utiliser l'API MySQL standard.
Le module MySQLWrapper permet de faciliter son utilisation.

**Si vous l'utilisez, n'hésitez pas à me tenir au courant (vous trouverez les informations pour me contacter par e-mail ou *via* Twitter sur [mon profil GitHub](https://github.com/cGuille)).**

Description rapide des fonctionnalités
======================================
  - Gestion des erreurs configurable : pas besoin de "or exit(mysql_error())". Le système lance automatiquement une erreur en cas de problème. Il est possible de choisir parmi trois modes d'erreur :
    - exception (par défaut) ;
    - erreur type "fatale" ;
    - erreur type "warning" ;
  - Échappement automatique des chaînes de caractères via un système de marqueurs. Pas besoin de rajouter les fastidieux "mysql_real_escape_string()" et de concaténer à tout bout de champ. Il existe cinq marqueurs :
    - « :int » : permet d'insérer un nombre entier. Une erreur est lancée si le paramètre correspondant n'est pas typé comme un integer ;
    - « :dec » : permet d'insérer un nombre décimal. Une erreur est lancée si le paramètre correspondant n'est pas typé comme un float ;
    - « :str » : permet d'insérer une chaîne de caractères. Elle est échappée via mysql_real_escape_string() et entourée de simples quotes ('), ce qui peut permettre d'éviter de les échapper dans la chaîne PHP ;
    - « :noq » : permet d'insérer une chaîne de caractères sans l'entourer de quotes (nom de table…). À utiliser en connaissance de causes. La chaîne est échappée via mysql_real_escape_string() ;
    - « :blo » : permet d'insérer un objet binaire. La valeur sera entourée de simples quotes (') et échappée via mysql_real_escape_string(). Peu utilisée, ce marqueur manque encore de tests ;
  - Les tuples peuvent être représentés par des tableaux (clés associatives ou numériques) ou par des objets de la classe standard de PHP. Une requête en lecture retournera un tableau de tuples même si un seul tuple est retourné. De cette façon, la valeur de retour d'une requête sélective est toujours traitée de la même façon (utilisation simple de count() + foreach()). Une requête en écriture renvoi le nombre de lignes affectées ;
  - Un exemple de code utilisant cette classe peut être trouvé dans [le fichier "use_case.php"](https://github.com/cGuille/MySQL_Wrapper/blob/master/use_case.php).
