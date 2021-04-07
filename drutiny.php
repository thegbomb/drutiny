<?php

$messages = [
  'Drutiny is preparing a super position of quantum uncertainty ... please wait 🧪',
  'Drutiny is reversing polarity with a sonic screwdriver ... please wait',
  'Drutiny is co-ordinating with Ziggy for a Quantum Leap ... please wait ⚛️',
  'Drutiny is hailing the USS Discovery ... Black Alert 🌌',
];
$container->setParameter('progress_bar.loading_message', $messages[array_rand($messages)]);
