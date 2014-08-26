<?php
namespace Codeception\Module;

use Codeception\Exception\ModuleConfig;
use Codeception\TestCase;

class DrupalMailSystem extends \Codeception\Module
{
    protected $requiredFields = array('enabled');
    protected $previous_mail_system;

    /**
     * @var bool
     *   If true, emails will not be cleared in _before().
     */
    protected $preserve_emails = false;

    /**
     * Store the current mail system.
     *
     * @param array $settings
     */
    public function _beforeSuite($settings = array())
    {
        if (!$this->hasModule("DrupalVariable")) {
          throw new ModuleConfig(
              "DrupalMailSystem",
              "DrupalMailSystem requires DrupalVariable module."
          );
        }

        if ($this->config['enabled']) {
            $this->previous_mail_system = $this->enableTestingMailSystem();
        }
    }

    /**
     * Restore the previous mail system.
     */
    public function _afterSuite()
    {
        if ($this->config['enabled']) {
            $this->restoreMailSystem();
        }
    }

    /**
     * Delete all stored emails prior to each test.
     */
    public function _before(TestCase $test)
    {
        if ($this->config['enabled']) {
            if (!$this->preserve_emails) {
                $this->clearSentEmails();
            }

            $this->previous_emails = false;
        }
    }

    /**
     * Enable the testing mail system.
     *
     * @return array
     *   The previous mail system.
     */
    protected function enableTestingMailSystem()
    {
        $system = $this->getModule("DrupalVariable")->getVariable("mail_system");
        if (!$system) {
            $system = array('default-system' => 'DefaultMailSystem');
        }

        $test_system = array('default-system' => 'TestingMailSystem');

        $this->getModule("DrupalVariable")
            ->haveVariable("mail_system", $test_system);
        $this->clearSentEmails();

        return $system;
    }

    /**
     * Restore the previous mail system.
     */
    protected function restoreMailSystem()
    {
        if (empty($this->previous_mail_system)) {
            throw new \LogicException("previous_mail_system has not been set yet.");
        }

        $this->getModule("DrupalVariable")
            ->haveVariable("mail_system", $this->previous_mail_system);
    }

    /**
     * Clear any sent emails.
     *
     * Useful in preparation for next tests.
     */
    public function clearSentEmails()
    {
        $this->getModule("DrupalVariable")
          ->dontHaveVariable('drupal_test_email_collector');
    }

    /**
     * Return the emails sent so far.
     *
     * @return array
     *   An array of message arrays, as described in hook_mail_alter().
     */
    public function grabSentEmails()
    {
        return $this->getModule("DrupalVariable")
          ->getVariable("drupal_test_email_collector", array());
    }

    /**
     * Can the user see an email sent with properties matching all criteria.
     *
     * @param array $criteria
     *   Key => value array, where key is the array key of a message array as
     *   described in hook_mail_alter() and value is the substring to search for. e.g.
     *   array("to" => "user@example.com", "body" => "hello world").
     */
    public function seeSentEmail(array $criteria)
    {
        $this->assert($this->proceedSeeSentEmail($criteria));
    }

    /**
     * Ensure the user cannot see an email sent with properties matching all criteria.
     *
     * @param array $criteria
     *   Key => value array, where key is the array key of a message array as
     *   described in hook_mail_alter() and value is the substring to search for. e.g.
     *   array("to" => "user@example.com", "body" => "hello world").
     */
    public function dontSeeSentEmail(array $criteria)
    {
        $this->assertNot($this->proceedSeeSentEmail($criteria));
    }

    /**
     * Match and email with with properties matching all criteria.
     *
     * @param array $criteria
     *   Key => value array, where key is the array key of a message array as
     *   described in hook_mail_alter() and value is the substring to search for. e.g.
     *   array("to" => "user@example.com", "body" => "hello world").
     */
    protected function proceedSeeSentEmail(array $criteria)
    {
        $emails = $this->grabSentEmails();

        $found = false;
        foreach ($emails as $email) {
            $matched = 0;
            foreach ($criteria as $key => $search) {
                if (strpos($email[$key], $search) !== false) {
                    $matched++;
                }
            }

            if ($matched == count($criteria)) {
                // all criteria matched.
                $found = true;
                break;
            }
        }

        return array("True", $found, "Testing whether email matching criteria has been found.");
    }

    /**
     * See number of emails sent.
     *
     * @param int $count
     *   Assert that this number of emails has been sent.
     */
    public function seeNumberOfEmailsSent($count)
    {
        $this->assert($this->proceedNumberOfEmailsSent($count));
    }

    /**
     * Don't see number of emails sent.
     *
     * @param int $count
     *   Assert that anything other than this number of emails have been sent.
     */
    public function dontSeeNumberOfEmailsSent($count)
    {
        $this->assertNot($this->proceedNumberOfEmailsSent($count));
    }

    /**
     * Logic for checking number of emails sent.
     *
     * @param int $count
     *   Number of emails.
     *
     * @return array
     */
    protected function proceedNumberOfEmailsSent($count)
    {
        $emails = $this->grabSentEmails();
        return array("Equals", $count, count($emails));
    }

    /**
     * Preserve emails for the next test.
     *
     * Will be cleared after that test unless preserveEmails() is called again.
     */
    public function preserveEmails()
    {
        $this->preserve_emails = true;
    }
}
