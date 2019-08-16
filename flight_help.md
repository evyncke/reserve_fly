# Application web de gestion des vols pour non-navigants

L'application est basée sur le concept d'un **vol** par vol d'initiation (avec instructeur) ou découverte (avec pilote de 100 heures PIC).

A chaque **vol** est associé un et un seul client, appelé **contact**. C'est la personne qui a contacté le RAPCS et avec qui il faut prévoir la date du vol, ...

Lors qu'un **vol** est ajouté dans le système, les étapes suivantes sont alors:
1) assigner un **pilote** **qualifié** pour ce vol;
2) au pilote alors de réserver un avion sur la page habituelle [de réservation](https://www.spa-aviation.be/resa/), le nom du **contact** apparaît alors dans la boîte de réservation;
3) après le **vol**, le pilote doit entrer sur la page _carnet de routes_ pour marquer non seulement une entrée dans le _carnet de routes_ de l'avion mais aussi marqué le vol comme effectué.

## Passagers
Si lors du contact, un vol est prévu avec des passages (non personne de contact), ceux-ci peuvent aussi être ajouté. Leurs poids sera demandé pour générer le _W&B_ obligatoire.

## Permissions
Seuls les membres connectés et étant dans le groupe _Administrateurs_ (c-à-d les administrateurs de l'ASBL mais aussi les webmasters) ont accès à cette application. Les *pilotes* eux utilisent l'application _réservation_ standard pour planifier et logger les vols.

**A FAIRE**: l'envoi d'un email avec toutes les informations au pilote désigné pour qu'il puisse planifier le vol.

## Qualification des pilotes
Via le [Pilotes -> Qualifications](https://www.spa-aviation.be/resa/flight_pilot_rating.php) il est possible de donner / enlever les privilèges de vols découverte ou initiation à tous les **pilote** de notre club.

Afin de pouvoir calculer le _W&B_, le poids du pilote est également demandé.

## Icones
Dans beaucoup d'endroits, il y a des petites icones:
* téléphone: pour lancer un appel téléphonique au pilote ou au contact, __de préférence à faire sur un smartphone ;-)__
* enveloppe: pour envoyer un email
* disquette: pour enregistrer les changements effectués
* poubelle: pour effacer définitevement une ligne d'information
* imprimante: pour générer un fichier PDF contenant toutes les infos légales pour le vol: liste des passagers, W&B, ...

## Note important
Application à tester et retester avant mise en production évidemment.