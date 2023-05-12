<?php
/**
 * Class UserNav
 *
 * UserNav documentation goes here. Explain
 * what this class does in a few lines.
 *
 * Example Usage:
 * if(true) {
 *   dosomething();
 * }
 *
 * @package Hphio\Api\Auth
 * @access public
 * @since 7.4
 */


namespace Hph\Auth;

use Exception;
use Hph\ApiService;
use Laminas\Diactoros\Response\JsonResponse;
use PDO;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class UserNav extends ApiService
{

    /**
     * Hit the database to get a list of the navigation panels that should be available
     * to the logged in user based on their role.
     *
     * 1. Check impersonation permissions. If you are you, or allowed to be someone else, OK.
     * 2. Return the nav items ordered by order (asc).
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param array $routeArgs
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, array $routeArgs = []): ResponseInterface
    {
//        $permissions = $this->container->get(ImpersonatePermissions::class);
        $current_user = $this->container->get('current_user');

//        if($permissions->verifyImpersonationPermissions($current_user, $routeArgs['id']) == false) return new EmptyResponse(403);

        $navJson = $this->getUserNav($routeArgs['id']);

        return new JsonResponse($navJson, 200);
    }

    private function getUserNav($userId) {
        $user = $this->getRequestedUser($userId);
        $sql = "SELECT icon, title, link
                FROM `role_navigation_items`
                where role = :role_id
                and enabled = 1
                order by `order` asc";
        $db = $this->container->get('db');
        $stmt = $db->prepare($sql);
        $stmt->execute(['role_id' => $user->role]);
        if($stmt->errorCode() !== '00000') throw new Exception($stmt->errorInfo()[2]);

        $data = [];

        foreach($stmt->fetchAll(PDO::FETCH_OBJ) as $obj) {
            $navItem = [];
            $navItem['icon'] = $obj->icon;
            $navItem['title'] = $obj->title;
            $navItem['link'] = $obj->link;

            $data[] = $navItem;
        }

        return $data;

    }

}
