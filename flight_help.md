# Application web de gestion des vols pour non-navigants

L'application est basée sur le concept d'un **vol** soit vol d'initiation (avec instructeur vol) ou découverte (avec pilote de 100 heures PIC).

A chaque **vol** est associé un et un seul client, appelé **contact**. C'est la personne qui a contacté le RAPCS et avec qui il faut prévoir la date du vol, ...

Lors qu'un **vol** est ajouté dans le système, il reste encore à effectuer les étapes suivantes (en cliquant sur l'icone crayon devant le vol):

* assigner un **pilote** **qualifié** pour ce vol;
* si nécessaire indiquer ou modifier la date du vol;
* lier une réservation à ce vol, soit:
  - au pilote alors de réserver un avion sur la page habituelle [de réservation](https://www.spa-aviation.be/resa/) à la date souhaitée, le nom du **contact** apparaît alors dans la boîte de réservation;
  - ou aller sur la page visualisation d'un vol via le tab _vol > tous les vols_ afin de voir les réservations effectuées ce jour-là et cliquer sur l'icone lien pour lier la réservation de l'avion à ce vol;
* après le **vol**, le pilote doit entrer sur la page _carnet de routes_ pour marquer non seulement une entrée dans le _carnet de routes_ de l'avion mais aussi marquer le vol comme effectué.

## Formulaire d'inscription via le web
Lorsqu'un futur client fait une demande de réservation via le site web, en plus des emails générés automatiquement, cette demande est aussi ajoutée dans les demandes de vols à assigner/traiter.

## Passagers
Si lors du contact, un vol est prévu avec des passages (non personne de contact), ceux-ci peuvent aussi être ajouté. Leurs poids sera demandé pour générer le _W&B_ obligatoire.

## Permissions
Seuls les membres connectés et étant dans le groupe _Administrateurs_ (c-à-d les administrateurs de l'ASBL mais aussi les webmasters) ou _Pilotes vols découvertes_ ou _Managers vols découvertes_ ont accès à cette application. Les *pilotes* eux utilisent l'application _réservation_ standard pour planifier et logger les vols.

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
* une chaîne: pour lier une réservation existante à un vol.

## Note important
Application encore débutant. Ne pas hésiter à envoyer des suggestions ou 'bugs' à [eric@vyncke.org](mailto:eric@vyncke.org)
