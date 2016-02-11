<?php
namespace Codeception\Module;

use Codeception\Exception\ModuleConfig;
use Codeception\TestCase;

class DrupalMailSystem extends \Codeception\Module
{
    /**
     * @var array
     *   Array of required config fields.
     */
    protected $requiredFields = array('enabled');

    /**
     * @var string
     *   The previous email system in use.
     */
    protected $previousMailSystem;

    /**
     * @var bool
     *   If true, emails will not be cleared in _before().
     */
    protected $preserveEmails = false;

    /**
     * @var DrupalVariable
     */
    protected $variableModule;

    /**
     * Store the current mail system.
     *
     * @param array $settings
     *
     * @throws ModuleConfig
     *   If DrupalVariable module is not enabled.
     */
    public function _beforeSuite($settings = array())
    {
        if (!$this->hasModule("DrupalVariable")) {
            throw new ModuleConfig(
                "DrupalMailSystem",
                "DrupalMailSystem requires DrupalVariable module."
            );
        }

        $this->variableModule = $this->getModule("DrupalVariable");

        if ($this->config['enabled']) {
            $this->previousMailSystem = $this->enableTestingMailSystem();
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
            if (!$this->preserveEmails) {
                $this->clearSentEmails();
            }

            $this->preserveEmails = false;
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
        $system = $this->variableModule->getVariable("mail_system");
        if (!$system) {
            $system = array('default-system' => 'DefaultMailSystem');
        }

        $test_system = array('default-system' => 'TestingMailSystem');

        $this->variableModule
            ->haveVariable("mail_system", $test_system);

        $this->clearSentEmails();

        return $system;
    }

    /**
     * Restore the previous mail system.
     */
    protected function restoreMailSystem()
    {
        if (empty($this->previousMailSystem)) {
            throw new \LogicException("previous_mail_system has not been set yet.");
        }

        $this->variableModule
            ->haveVariable("mail_system", $this->previousMailSystem);
    }

    /**
     * Clear any sent emails.
     *
     * Useful in preparation for next tests.
     */
    public function clearSentEmails()
    {
        $this->variableModule
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
        return $this->variableModule
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
     * Match an email where properties match all criteria.
     *
     * @param array $criteria
     *   Key => value array, where key is the array key of a message array as
     *   described in hook_mail_alter() and value is the substring to search for. e.g.
     *   array("to" => "user@example.com", "body" => "hello world").
     *
     * @return array
     *   Array compatible with assert/assertNot.
     */
    protected function proceedSeeSentEmail(array $criteria)
    {
        $emails = $this->grabSentEmails();

        $found = false;
        foreach ((array) $emails as $email) {
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
        $this->preserveEmails = true;
    }
}
