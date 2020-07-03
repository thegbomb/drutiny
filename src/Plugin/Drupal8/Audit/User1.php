<?php

namespace Drutiny\Plugin\Drupal8\Audit;

use Drutiny\Audit;
use Drutiny\Sandbox\Sandbox;
use Drutiny\Audit\RemediableInterface;

/**
 * User #1
 */
class User1 extends Audit implements RemediableInterface
{


    public function configure()
    {
        $this->addParameter(
           'email',
           static::PARAMETER_OPTIONAL,
           'The email the user account should be.',
        );
        $this->addParameter(
            'blacklist',
            static::PARAMETER_OPTIONAL,
            'List of usernames that are not acceptable.',
        );
        $this->addParameter(
            'status',
            static::PARAMETER_OPTIONAL,
            'Whether the account should be enabled or disabled.',
        );
    }

  /**
   *
   */
    public function audit(Sandbox $sandbox)
    {
      // Get the details for user #1.
        $user = $sandbox->drush(['format' => 'json'])
                    ->userInformation(1);

        $user = (object) array_pop($user);

        $this->set('user', $user);
        $this->set('blacklist_fail', false);
        $this->set('email_fail', false);
        $this->set('status_fail', false);

        $errors = [];
        $fixups = [];

      // Username.
        $pattern = $this->getParameter('blacklist');
        if (preg_match("#${pattern}#i", $user->name)) {
            $errors[] = "Username '$user->name' is too easy to guess.";
            $this->set('blacklist_fail', true);
        }
        $this->set('username', $user->name);

      // Email address.
        $email = $this->getParameter('email');

        if (!empty($email) && ($email !== $user->mail)) {
            $errors[] = "Email address '$user->mail' is not set correctly.";
            $this->set('email_fail', true);
        }

      // Status.
        $status = (bool) $this->getParameter('status');
        if ($status !== (bool) $user->user_status) {
            $errors[] = 'Status is not set correctly. Should be ' . ($user->user_status ? 'active' : 'inactive') . '.';
            $this->set('status_fail', true);
        }

        $this->set('errors', $errors);
        return empty($errors) ? true : Audit::WARNING;
    }

    public function remediate(Sandbox $sandbox)
    {

      // Get the details for user #1.
        $user = $sandbox->drush(['format' => 'json'])
                    ->userInformation(1);

        $user = (object) array_pop($user);

        $output = $sandbox->drush()->evaluate(function ($uid, $status, $password, $email, $username) {
            $user =  \Drupal\user\Entity\User::load($uid);
            if ($status) {
                $user->activate();
            } else {
                $user->block();
            }
            $user->setPassword($password);
            $user->setEmail($email);
            $user->setUsername($username);
            $user->set('init', $email);
            $user->save();
            return true;
        }, [
        'uid' => $user->uid,
        'status' => (int) (bool) $this->getParameter('status'),
        'password' => $this->generateRandomString(),
        'email' => $this->getParameter('email'),
        'username' => $this->generateRandomString()
        ]);

        return $this->audit($sandbox);
    }

  /**
   * Generate a random string.
   *
   * @param int $length
   *   [optional]
   *   the length of the random string.
   *
   * @return string
   *   the random string.
   */
    public function generateRandomString($length = 32)
    {

      // Generate a lot of random characters.
        $state = bin2hex(random_bytes($length * 2));

      // Remove non-alphanumeric characters.
        $state = preg_replace("/[^a-zA-Z0-9]/", '', $state);

      // Trim it down.
        return substr($state, 0, $length);
    }
}
