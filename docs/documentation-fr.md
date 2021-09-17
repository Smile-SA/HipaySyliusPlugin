Pour faciliter l'écriture et la compréhension, cette documentation sera écrite en français.
Elle parmettra d'ajouter des précisions au README d'installation et de proposer un état des lieux au moment du rendu.

## Etat d'avancement

### GatewayFactory

L'intégration des 4 modes de paiement dans le BO est finalisée. Il est donc désormais possible d'ajouter dans le BO :
- Paiement Hipay CB
- Paiement Hipay CB Mo/TO
- Paiement Oney 3x
- Paiement Oney 4x
 
Pour chaque mode de paiement, on retrouve une `GatewayFactory` dans `src/Payum/Factory/` ainsi qu'une définition de service dans `config/services/gateway_factory.yaml`
=> Lien utile pour cela => https://docs.sylius.com/en/1.10/cookbook/payments/custom-payment-gateway.html
 
Ensuite, pour le paiement Oney, plusieurs champs sont disponibles dans l'écran de "Moyens de paiement" comme :

- Code OPC
- Avec Frais
- Cout Minimum panier requis
- Cout Maximum panier requis

Tous ces champs sont configurés dans `src/Form/Type/HipayOneyConfigurationType.php` 
ainsi qu'une définition de service dans `config/services/form.yaml` où on définit sur quelle Gateway utilisé ce form.

Exemple :

```yaml
# config/form.yaml

    Smile\HipaySyliusPlugin\Form\Type\HipayOneyConfigurationType:
        class: Smile\HipaySyliusPlugin\Form\Type\HipayOneyConfigurationType
        tags:
            - { name: sylius.gateway_configuration_type, type: hipay_oney3, label: smile_hipay_sylius_plugin.ui.hipay_oney3_gateway_label }
            - { name: sylius.gateway_configuration_type, type: hipay_oney4, label: smile_hipay_sylius_plugin.ui.hipay_oney4_gateway_label }
            - { name: form.type }
```

### Hosted Field

En ce qui concerne le frontoffice, le paiment MO/TO et le paiement CB classique utilise des hosted field.
La surcharge du `_choice.html.twig` expliqué dans le README permet de faire un render du controller qui gère les hostedFields => `src/Controller/HostedFieldController.php`

Le controller à la tâche de récupérer les credentials et de les passer au template `templates/SyliusShopBundle/Checkout/hipay_fields.html.twig`.
Le template se charge de créer des conteneurs vides qui vont ensuite être enrichis grâce au javascript de configuration ainsi qu'au SDK JS d'Hipay (à ajouter comme dans le README).

Ce fichier JS se trouve ici => `public/hostedfields.js`. N'étant pas du tout à l'aise avec ce langage, je pense qu'il faudra le retravailler par quelqu'un de plus spécialisé.
La gestion des erreurs n'a pas été implémenté et priorisé car cela sera spécifique à l'intégration faites dans le projet.

Ensuite pour le fonctionnement global, une fois la carte renseignée et valide et qu'on passe à l'étape suivante (action de submit du form), 
on passe par `src/EventSubscriber/HipayStoreTokenPaymentEventSubscriber.php` qui va stocker le `token_card` et le `payment_product` dans une session nommée `src/Context/PaymentContext.php`.
Cela permet à l'étape de récap de conserver de passer les information de payment à la transaction. A la fin du process, la session retire les informartions stockées. 

=> Lien utilie vers la documentation et la démo des hostedfields pour reprendre le fichier JS=> https://codesandbox.io/s/p7s1c

Pour s'y retrouver dans le JS, quelques informations utiles :

```js
  const formPaymentMethod = document.getElementById('form-select-payment');
```
`form-select-payment` est le nom du form de sylius


```js
document.querySelector('input[name="sylius_checkout_select_payment[payments][0][method]"]')
```
- `sylius_checkout_select_payment[payments][0][method]`, nom du champ généré par Sylius pour les modes de paiement dans le checktou
- `sylius_checkout_select_payment[payments][1][method]`, nom du champ généré par Sylius pour les modes de paiement dans le compte client

## Création de la transaction 

C'est dans la class `src/Api/CreateTransaction.php` que l'on prépare ce qui va être envoyé à Hipay. N'ayant pas eu beaucoup de spécification sur ce sujet, les infos envoyés sont le strict minimum pour Hipay avec des données de test. C'est pourquoi, il faudra enrichir ces informations avec celles nécessaires pour le projet.
 
## Action avec Payum

Les modes de paiement Sylius fonctionnent avec le bundle Payum. Le principe est de se mapper sur differentes actions du workflow de Payum pour y faire notre implémentation. 
Payum génère donc un token utilisé pour appelé les différentes action.
On retrouve donc dans `Payum/Action` toutes celles qui nous intéressent.

- `CaptureAction` est celle qui va effectuer la création de la transaction est l'envoyer à l'api Hipay, elle va également stocker certaines informations dans le détails du paiement (notamment le payum_token utile pour la route Notify).
- `StatusAction` est ensuite celle qui va s'occuper de la gestion du status en fonction du code de retour de la request hipay. On peut donc faire un `markCaptured` lorsque tout est OK ou alors  `markFailed` lorsque cela a échoué.
Payum se charge ensuite de faire le nécessaire avec ces informations
- `ResolveNextRouteAction` permet, une fois tout ces process terminés, de rediriger sur la page de paiement ou ailleurs lorsqu'il y a un problème (Ceci devra être modifié en fonction des spécifications du projet)
- `NotificationAction` est l'action qui va traiter les notifications d'Hipay lorsque celui ci voudra nous notifier d'un changement de status d'une commande. C'est ici que l'on vérifie que la signature est la bonne pour, ensuite, renvoyer tout ça au `StatusAction`

## Reste à faire

Il reste pas mal de choses à faire/tester car les conditions de travail ou de test n'étaientt pas optimales. Un grand merci à Florian pour sa réactivité lorsque j'avais des questions.
Grâce à lui, nous avons pu avoir des comptes de tests spécifiques à l'environnement Symfony alors qu'au départ c'était couplé avec le Magento. Nous avons donc pu valider que les transactions MO/TO et CB classique fonctionnait bien. 

Par contre, nous n'avons pas pu tester Oney 3x et 4x puisque les comptes demandaient une validation côté Hipay puis Oney. C'est en cours de demande donc il faudra faire les tests
pour voir si cela fonctionne. 

Même "problème" pour la route de notification. Payum ne proposant pas une route générique, j'ai du créé un controller custom pour cela ce qui permet de récupérer le payment en BDD grâce à l'orderId envoyé par Hipay.
Cela permet ensuite de récupérer dans le détails du paiement le `payum_token` pour ensuite appelé le process normal de Payum avec le token.

Cette partie a également été développée en "aveugle" faute de temps et de difficulté de tests de cette route de Notification.

La route à paramétrer côté Hipay est => `url-du-site/payum/hipay/notify-generic` 

## Liens utiles

- Pour le paiement Oney 3x 4x => https://support.hipay.com/hc/fr/articles/360001153865-HiPay-Enterprise-3x-4x-Oney
- Pour les cartes de test => https://support.hipay.com/hc/fr/articles/213882649-Comment-tester-les-m%C3%A9thodes-de-paiement-
- Pour le PHP SDK => https://developer.hipay.com/online-payments/sdk-reference/sdk-php
- Pour le JS SDK => https://developer.hipay.com/online-payments/sdk-reference/sdk-js
