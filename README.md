# ProjetApplicationMetier
 STEAM2.0
## Rôles :
- Consommateur
- Content Creator
- Moderator

## Règles de Gestion :
- Vérifier que le Consommateur à payer avant de lui donner le contenu qu'il demande.
- Vérifier que le Content Creator est éligible pour publier du contenu.
- Vérifier que le compte qui souhaite bannir un user ou un content creator est bel et bien Moderator.

## Risques Métier en cas d'incident :
- Le Consommateur ne peut plus acheter de contenu.
- Le Content Creator ne voit plus les contenus qu'il a publié.
- Le Moderator ne peut plus bannir qui que ce soit.

## Users Story :
- Consommateur peut : 
    - Acheter des contenus.
    - Liker des contenus.
- Consommateur ne peut pas : 
    - Créer et publier du contenu.
    - Bannir des User et des Content Creator. 
- Content Creator peut :
    - Créer et publier du contenu.
    - Avoir accès à la page de stat de leurs jeux
    - Avoir les mêmes droits que les Consommateurs.
- Content Creator ne peut pas :
    - Bannir des User et des Content Creator.
- Moderator peut :
    - Bannir des User et des Content Creator.
    - Avoir accès à la page de stat global
    - Avoir les mêmes droits que les Consommateurs.
    - Avoir les mêmes droits que les Content Creator.
- Moderator ne peut pas :
    - Bannir d'autres Moderator.
## Matrice de droit :

|          | publier du contenu | Liker du contenu | Acheter du contenu | Bannir des Consommateurs et des content creator | Accès à la page de stat d'un jeux | Accès à la page de stat global | 
|-----------------|---|---|---|---|---|---|
| Moderateur      | x | x | x | x | x | x |
| Content creator | x | x | x |   | x |   |
| Consommateur    |   | x | x |   |   |   |
