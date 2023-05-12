<?php declare(strict_types=1);

namespace Tests;

use DateTime;
use DateTimeZone;
use Exception;
use Hph\Models\User;
use Hph\Users\PasswordResetService;
use Hph\RandomGenerator;
use Hph\QueryRunner;
use Laminas\Diactoros\Stream;
use League\Container\Container;
use Library\Api\Signup\SignupService;
use PDO;
use PDOException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use ReflectionClass;

class LibraryTest extends TestCase
{
    static protected ?PDO $pdo = null;
    protected ?PDO $conn = null;
    protected ?Container $container = null;

    protected ?string $defaultsFilePath = null;
    protected ?MockObject $request = null;
    protected $serverParams = [];

    protected function getContainer()
    {
        return $this->container;
    }

    protected function buildContainer($filter = []): LibraryTest
    {
        unset($this->container);
        $this->container = new Container();

        $this->loadContainerizedClasses($filter);

        return $this;
    }

    /**
     * alias of mockDateTime().
     * @return $this
     */
    protected function withDejaVu($timestamp = 728654400, $timezone = "GMT"): LibraryTest
    {
        return $this->mockDateTime($timestamp, $timezone);
    }

    /**
     * Mock DateTime so that it always returns Groundhog Day of 1993.
     * See: https://www.imdb.com/title/tt0107048/
     * @return $this
     */
    protected function mockDateTime($timestamp = 728654400, $timezone = "GMT"): LibraryTest
    {
        $mockDateTime = $this->getMockBuilder(DateTime::class)
            ->setConstructorArgs(["@" . $timestamp, new DateTimeZone($timezone)])
            ->onlyMethods([]) //Allow all original methods to work.
            ->getMock();

        $this->container->add(DateTime::class, $mockDateTime);

        return $this;
    }

    /**
     * Mock PDO for the database.
     * @return $this
     */
    protected function mockDB(): LibraryTest
    {
        $mockPDO = $this->getMockBuilder(PDO::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->container->add('db', $mockPDO);

        return $this;
    }

    private function UserServices(array $containerizedClasses): array
    {

        $containerizedClasses[] = SignupService::class;
        $containerizedClasses[] = QueryRunner::class;
        $containerizedClasses[] = PasswordResetService::class;

        return $containerizedClasses;
    }



  /**
     * Load all the classes that require the container in their constructor.
     * @param $filter
     * @param $container
     * @return void
     */
    private function loadContainerizedClasses($filter): void
    {
        $containerizedClasses = [];
        $containerizedClasses = $this->UserServices($containerizedClasses);



        $finalClasses = array_diff($containerizedClasses, $filter);
        foreach ($finalClasses as $class) {
            $this->container->add($class)->addArgument($this->container);
        }
    }



    /**
     * Add the system config into the container.
     * @return $this
     */
    protected function withConfig()
    {
        $this->container->add('config', getConfigs(getConfigValues()));
        return $this;
    }

    private function DatabaseTools(array $containerizedClasses)
    {
        $containerizedClasses[] = QueryRunner::class;
        return $containerizedClasses;
    }

    /**
     * Mock the HPHIO\RandomGenerator so that it gives determinant, consecutive, predictable values.
     * @return void
     */
    protected function withoutRandomness(): LibraryTest
    {
        $notSoRandom = $this->createMock(RandomGenerator::class);
        $notSoRandom->method('uuidv4')->will(
            $this->onConsecutiveCalls(
                '9f052830-c39a-4bac-81a2-64bf78c38030',
                "0742a8c0-47ec-4ade-a1fa-916ee7d48f15",
                "4c47a109-e754-4316-b7d0-c58e11daad71",
                "dd723f07-93b7-4f12-b226-0ab57f8e93b8",
                "643b7d2c-7c37-46fa-832b-37bfa3999ccc",
                "e98247f6-df59-4ece-a299-f97f6e25f3d9",
                "abc9f316-85a5-48b7-b195-2ec25d0c4b1c",
                "efa0e957-69e4-4d6f-921c-fb4239812a8b",
                "6442729c-e962-4482-88ba-7ca1a6aa80dd",
                "618506c2-9b2d-42dd-b3a8-03e1b9bb5ef7",
                "416f2434-a3b9-4ce6-b1e6-75168fe682cb",
                "55e9abf2-1636-44d0-94a3-af74611107bb",
                "e94360f8-12d5-4bd3-aad9-62c69211d8e1",
                "b51777a4-e70a-4aa4-86b3-63baf6df47f9",
                "0b1e6833-c0fb-452f-a0ae-318b4c5d096c"
            )
        );

        $this->container->add(RandomGenerator::class, $notSoRandom);
        return $this;
    }

    /**
     * Provide a mock PHPMailer instance to test email sends.
     */

    protected function withNotifications() : LibraryTest {
        $mockMailer = $this->getMockBuilder(PHPMailer::class)
            ->onlyMethods(['send']) //Allow all original methods to work.
            ->getMock();

        $mockMailer->method('send')->willReturn(true);

        $this->container->add(DummyEmailEnabledService::class)->addArgument($this->container);
        $this->container->add(PHPMailer::class, $mockMailer);
        return $this;
    }

    /**
     * Add the database connection configured in phpunit.xml to the container as 'db'.
     * @return void
     */
    protected function withDatabase(): LibraryTest
    {
        if ($this->conn === null)
            $this->connectToDatabase();
        $this->container->add('db', $this->conn);
        return $this;
    }

    private function connectToDatabase()
    {
        if (self::$pdo == null) {
            try {
                self::$pdo = new PDO($GLOBALS['DB_DSN'], $GLOBALS['DB_USER'], $GLOBALS['DB_PASSWD'], [PDO::MYSQL_ATTR_LOCAL_INFILE => true]);
                self::$pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
                self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (PDOException $e) {
                throw new Exception("Connection failed: " . $e->getMessage());
            }
        }

        $this->conn = self::$pdo;
    }

    /**
     * Load the dataset for this test.
     */
    protected function loadDataSet()
    {

        $sqlFile = $this->getFixturePath();

        $this->writeDefaultsFile();
        $this->importSql($sqlFile);
        $this->destroyDefaultsFile();
    }

    private function importSql($sqlFile)
    {
        if (!file_exists($sqlFile))
            throw new Exception("$sqlFile does not exist.");

        $cmd = 'mysql --defaults-file="{FILE}" support_local < {SQLFILE}';
        $cmd = str_replace("{FILE}", $this->defaultsFilePath, $cmd);
        $cmd = str_replace("{SQLFILE}", $sqlFile, $cmd);
        $buffer = shell_exec($cmd);
    }

    protected function writeDefaultsFile()
    {
        $template = <<<EOF
[client]
user={USER}
password={PASS}

[mysqladmin]
user={USER}
password={PASS}

EOF;

        if ($this->defaultsFilePath === null)
            $this->defaultsFilePath = tempnam(sys_get_temp_dir(), "tmp_");

        $fh = fopen($this->defaultsFilePath, 'w');
        $output = str_replace("{USER}", $GLOBALS['DB_USER'], $template);
        $output = str_replace("{PASS}", $GLOBALS['DB_PASSWD'], $output);

        fwrite($fh, $output);
        fclose($fh);
    }

    protected function destroyDefaultsFile()
    {
        if (file_exists($this->defaultsFilePath))
            unlink($this->defaultsFilePath);
    }

    protected function getFixturePath()
    {
        //Build the path from the app root (stored in bootstrap.php as a $GLOBAL)
        $fixturePath = explode('.', $GLOBALS['appRoot']);

        //add 'tests/' to the base path.
        array_push($fixturePath, 'tests');

        // Build the rest of the path from the namespace and file name.
        //Get the class, and pull of the "Tests" because we already added the 'tests/' directory.
        $className = get_class($this);
        $reflection_class = new ReflectionClass($className);
        $namespace = $reflection_class->getNamespaceName();
        $nsBuffer = explode('\\', $namespace);
        array_shift($nsBuffer);
        array_push($nsBuffer, "fixtures");

        //add these to the figure path:
        $fixturePath = array_merge($fixturePath, $nsBuffer);

        //Finally, add the file name on.
        $filenameBuffer = explode('\\', $className);
        $sqlFile = array_pop($filenameBuffer) . '.sql';
        array_push($fixturePath, $sqlFile);

        $sqlFile = implode('/', $fixturePath);
        return $sqlFile;
    }

    public function withCurrentTime()
    {
        $now = new DateTime();
        $this->container->add(DateTime::class, $now);
        return $this;
    }

    /**
     * Begin to build out a mock server request. Sets $this->request to the mock object.
     */
    public function buildMockServerRequest(): LibraryTest
    {
        $this->request = $this->getMockBuilder(ServerRequestInterface::class)
            ->getMock();
        return $this;
    }

    /**
     * Add a request body (typically JSON) to the payload.
     * $body = '{"message":"hello world."}';
     * $request = $this->buildMockServerRequest()
     *      ->withRequestBody($body)
     *      ->getRequest();
     * @param string $body
     */
    public function withRequestBody(string $body): LibraryTest
    {
        $jsonPayload = $body;
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $jsonPayload);
        rewind($stream);

        $this->request->method('getBody')->willReturn(new Stream($stream));
        return $this;
    }

    /**
     * Add server parameters to the request.
     * $serverParams = [
     *      'REQUEST_METHOD' => $method
     *      , 'REQUEST_URI'  => $uri
     *  ];
     * $request = $this->buildMockServerRequest()
     *      ->withServerParams($serverParams)
     *      ->getRequest();
     * @param array $serverParams
     */
    public function addServerParam(string $parameter, $value): LibraryTest
    {
        $this->serverParams[$parameter] = $value;
        return $this;
    }

    /**
     * @param $uri
     * @return void
     * Set the server request URI for the request.
     * $request = $this->buildMockServerRequest()
     *      ->useRequestURI('/clients/100')
     *      ->getRequest();
     */
    public function useRequestURI($uri): LibraryTest
    {
        $uri = "/api/v1" . $uri;
        $mockURI = $this->getMockBuilder(UriInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockURI->method('getPath')->willReturn($uri);
        $this->request->method('getUri')->willReturn($mockURI);
        $this->addServerParam('REQUEST_URI', $uri);
        return $this;
    }

    /**
     * Set the HTTP VERB (Method) of the request.
     * $request = $this->buildMockServerRequest()
     *      ->useMethod('POST')
     *      ->getRequest();
     * @param string $method
     */
    public function useMethod(string $method = 'GET'): LibraryTest
    {
        $this->addServerParam('REQUEST_METHOD', $method);
        $this->request->method('getMethod')->willReturn($method);

        return $this;
    }

    /**
     * Adds headers to the requst.
     * $defaultHeaders = [
     * 'Authentication' => 'Bearer eyJ0eXAiOiJ',
     * 'Content-Type' => 'application/json'
     * ];
     * $request = $this->buildMockServerRequest()
     *      ->withHeaders($defaultHeaders)
     *      ->getRequest();
     * @param array $headers
     */
    public function withHeaders(array $headers): LibraryTest
    {
        $this->request->method('getHeaders')->willReturn($headers);
        $hasHeaderMap = $this->renderHasHeaderMap($headers);
        $getHeaderMap = $this->renderGetHeaderMap($headers);

        $this->request->method('hasHeader')
            ->will($this->returnValueMap($hasHeaderMap));

        $this->request->method('getHeader')
            ->will($this->returnValueMap($getHeaderMap));

        return $this;
    }

    /**
     * Return applies the server params, if any, and then returns the mock server request.
     */
    public function getRequest()
    {
        $this->request->method('getServerParams')->willReturn($this->serverParams);
        return $this->request;
    }

    /**
     * Builds the header map to tell the request if a header exists or not.
     * @param array $headers
     * @return void
     */
    private function renderHasHeaderMap(array $headers): array
    {
        $hasHeaderMap = [];
        foreach ($headers as $header => $value) {
            $hasHeaderMap[] = [$header, true];
        }

        return $hasHeaderMap;
    }

    /**
     * Build the header map to tell the request the value of each header.
     * @param array $headers
     * @return array
     */
    private function renderGetHeaderMap(array $headers): array
    {
        $getHeaderMap = [];
        foreach ($headers as $header => $value) {
            $getHeaderMap[] = [$header, [$value]];
        }

        return $getHeaderMap;
    }

    public function withCurrentUser(User $user): LibraryTest
    {
        $this->container->add('current_user', $user);
        return $this;
    }

    public function createdJustNow(string $datetime)
    {
        $timestamp = strtotime($datetime);
        $created = new DateTime('@' . $timestamp);
        $now = new DateTime();
        $diff = $now->diff($created);
        $success = true;
        $success = $success && ($diff->y == 0);
        $success = $success && ($diff->m == 0);
        $success = $success && ($diff->d == 0);
        $success = $success && ($diff->h == 0);
        $success = $success && ($diff->i == 0);
        $success = $success && ($diff->s < 59);
        return $success;
    }

    public function withFiles(array $files): LibraryTest {
        $this->request->method('getUploadedFiles')->willReturn($files);

        $files['uploaded_file']->getSize();
        return $this;
    }

    public function withPost(array $post): LibraryTest {
        $this->request->method('getParsedBody')->willReturn($post);
        return $this;
    }



}
