<?php
/**
 * {CLASS SUMMARY}
 *
 * Date: 7/26/18
 * Time: 6:54 PM
 * @author Michael Munger <michael@highpoweredhelp.com>
 */

namespace Hph\Farret;

use \Exception;

class Notif
{
    private $tagPattern       = '/\{{2} {0,2}([A-Z]+) {0,2}\}{2}/';
    private $hookPattern      = '/(?:{\%\s{0,})([A-Z]+)\s{0,}((\|(?:{{){0,1}[A-Za-z0-9-]+(?:}}){0,1}\s{0,}){0,})(?:\%})/';

    public $templateDirectory = null;
    public $template          = null;
    public $fromName          = null;
    public $fromAddress       = null;
    public $subjectTemplate   = null;
    public $body              = null;

    public $to                = [];
    public $cc                = [];
    public $bcc               = [];
    public $fartDictionary    = [];

    public $hooks             = [];

    public function __construct()
    {
        $this->addHook('DATE', 'getDate');
        $this->addHook('HASH', 'hash');

    }

    public function setBody($body) {
        $this->body = $body;
    }

    public function getBody() {
        return $this->body;
    }

    public function setTemplate($template) {
        $this->template = $template;
    }

    public function getTemplate() {
        return $this->template;
    }

    public function setTemplateDirectory($directory)
    {
        if (file_exists($directory) === false) {
            return false;
        }

        $this->templateDirectory = $directory;
        return file_exists($this->templateDirectory);
    }

    /**
     * Loads a template from the template directory.
     * @param $template
     * @throws Exception
     */

    public function loadTemplate($template)
    {
        $targetTemplate = $this->templateDirectory . "$template.html";

        if (file_exists($this->templateDirectory) === false) {
            throw new Exception("Template directory not set!");
        }
        if (file_exists($targetTemplate)          === false) {
            throw new Exception("Requested template does not exist in $targetTemplate");
        }

        $this->template = file_get_contents($targetTemplate);

        return strlen($this->template) > 0;
    }

    public function setFromName($name)
    {
        $this->fromName = $name;
    }

    /**
     * Validates an email passed to it, and if valid, sets the from address for the email notification.
     * @param $email
     * @return bool
     * @throws Exception
     */

    public function setFromAddress($email)
    {
        $email = filter_var($email, FILTER_VALIDATE_EMAIL);

        if ($email === false) {
            throw new Exception("$email is not a valid email address!");
        }

        $this->fromAddress = $email;

        return true;
    }

    public function setSubjectTemplate($subject)
    {
        $this->subjectTemplate = $subject;
    }

    /**
     * Adds a Find And Replace Template pair. These will be used to find {{ TEMPLATETAGS }} and perform a substitution.
     * @param $find
     * @param $replace
     */

    public function addFart($find, $replace)
    {
        $this->fartDictionary[$find] = $replace;
    }

    /**
     * Returns unmatched, unreplaced tags from the template.
     * @return mixed
     * @throws Exception
     */

    public function getTemplateTags()
    {
        if (strlen($this->template) == 0) {
            throw new Exception("Template not set!");
        }
        return $this->getTags($this->template);
    }

    public function getBodyTags()
    {
        return $this->getTags($this->body);
    }

    public function getTags($body)
    {
        $matches = [];
        preg_match_all($this->tagPattern, $body, $matches, PREG_PATTERN_ORDER);

        $TagFactory = new TagFactory();
        $buffer = [];

        foreach($matches[0] as $match) {
            $tag = $TagFactory->getTag($match);
            $buffer[] = $tag;
        }

        return $buffer;
    }

    public function getHooks($body)
    {
        $matches = [];
        preg_match_all($this->hookPattern, $body, $matches, PREG_PATTERN_ORDER);
        return $matches[0];
    }

    public function makeTag($find)
    {
        return sprintf("{{%s}}", strtoupper($find));
    }

    public function matchFind($tag, $find)
    {
        //Remove spaces
        $tag = str_replace(" ", '', $tag);
        //Decorate the find
        $find = $this->makeTag($find);
        return (strcmp($tag, $find) === 0);
    }

    /**
     * @param $body
     * @throws Exception
     */

    private function doFart($body, $tags)
    {

        foreach ($tags as $tag) {

            $tag->fart($this->fartDictionary);

            if(strpos($body, $tag->getTag()) === false) {
                // @codeCoverageIgnoreStart
                continue;
                // @codeCoverageIgnoreEnd
            }

            $body = str_replace($tag->getTag(), $tag->getReplacement(), $body);
        }

        $tags = $this->getTags($body);

        if (count($tags) > 0) {
            $body = $this->doFart($body, $tags);
        }

        return $body;
    }

    /**
     * @throws Exception
     */

    public function render()
    {
        $this->body = $this->doFart($this->template, $this->getTemplateTags());
        $this->body = $this->renderAllHooks($this->body);
    }

    /**
     * @param $body
     * @return mixed
     * @throws Exception
     *
     */

    public function renderAllHooks($body) {
        $hooks = $this->getHooks($body);
        $TagFactory = new TagFactory();
        $tags = [];

        foreach($hooks as $hook) {
            $Tag = $TagFactory->getTag($hook);
            $Tag->setReplacement($this->renderHook($hook));
            $body = str_replace($hook, $Tag->getReplacement(), $body);
        }

        // Hooks are not recursive now. But they could be if we uncomment this.
        // $hooks = $this->getHooks($body);
        // if(count($hooks) > 0) {
        //     $this->renderAllHooks($body);
        // }

        return $body;
    }

    public function addHook($hook, $callback)
    {
        $this->hooks[$hook] = $callback;
    }

    /**
     * @param $hook
     * @return mixed
     * @throws Exception
     */

    public function renderHook($hook)
    {

        $TagFactory = new TagFactory();
        $Tag = $TagFactory->getTag($hook);
        $Tag->fart($this->fartDictionary);
        $action = $Tag->getLabel();

        //2. Lookup the callback in the hooks dictionary.

        if(isset($this->hooks[$action]) === false) {
            throw new Exception('The callback you requested is not registered in the notification hooks.');
        }

        $callback = $this->hooks[$action];

        if (method_exists($this, $callback) === false) {
            throw new Exception("Hook method does not exist! Cannot execute $callback.");
        }

        return (count($Tag->getArgs()) == 0 ? $this->$callback() : $this->$callback($Tag->getArgs()));
    }

    public function getDate($formatArray)
    {
        $now = new \DateTime();
        return $now->format($formatArray[0]);
    }

    public function getCurrentMonth()
    {
        return $this->getDate(["m"]);
    }

    public function getCurrentDay()
    {
        return $this->getDate(["d"]);
    }

    public function getCurrentYear()
    {
        return $this->getDate(["Y"]);
    }

    public function getArgs($hook) {
        $Factory = new TagFactory();
        $Tag = $Factory->getTag($hook);
        return $Tag->getArgs();
    }

    public function hash($args) {
        $buffer = '';
        for($x = 0; $x < count($args); $x++ ) {
            $buffer = md5($buffer.$args[$x]);
        }

        return $buffer;
    }

}
