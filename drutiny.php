<?php

$messages = [
  'Drutiny is preparing of super position of quantum uncertainty... please wait 🧪',
  'Drutiny is reversing polarity on the Tardus... please wait'
];
$container->setParameter('progress_bar.loading_message', $messages[array_rand($messages)]);
