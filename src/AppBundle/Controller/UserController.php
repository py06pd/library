<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

use Facebook\Facebook;
use Facebook\Exceptions\FacebookResponseException;
use Facebook\Exceptions\FacebookSDKException;
               
use AppBundle\Entity\User;

class UserController extends Controller
{
    /**
     * @Route("/myaccount/save")
     */
    public function myAccountSaveAction(Request $request)
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(array('status' => "error", 'errorMessage' => "You must be logged in to make request"));
        }
        
        $em = $this->getDoctrine()->getManager();
        
        $user->name = $request->request->get('name');
        $user->username = $request->request->get('username');
        
        $password = trim($request->request->get('password'));
        
        if ($user->name == '' || $user->username == '' || $password == '') {
            return $this->json(array('status' => "warn", 'errorMessage' => "Invalid form data"));
        }
        
        if ($password !== "********") {
            $salt = substr(hash("sha256", mt_rand(0, 100)), 0, 16);
            $user->password = $salt . hash_hmac("sha256", $salt . $password, $this->getParameter('secret'));
        }
        
        $em->flush();
        
        return $this->json(array('status' => "OK"));
    }
    
    /**
     * @Route("/users/delete")
     */
    public function deleteAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $repo = $em->getRepository(User::class);
        
        $users = $repo->findBy(array('id' => $request->request->get('ids')));
        foreach ($users as $item) {
            $em->remove($item);
        }
        $em->flush();
                
        return $this->json(array('status' => "OK"));
    }
    
    /**
     * @Route("/users/get")
     */
    public function getAction(Request $request)
    {
        $data = $this->getDoctrine()->getRepository(User::class)->findAll();
                
        return $this->json(array(
            'status' => "OK",
            'users' => $data
        ));
    }
    
    /**
     * @Route("/users/user")
     */
    public function userAction(Request $request)
    {
        $item = $this->getDoctrine()
                     ->getRepository(User::class)
                     ->findOneBy(array('id' => $request->request->get('id')));
        
        return $this->json(array('status' => "OK", 'data' => $item));
    }
    
    /**
     * @Route("/users/save")
     */
    public function saveAction(Request $request)
    {
        $dataItem = json_decode($request->request->get('data'), true);
        
        $em = $this->getDoctrine()->getManager();
        if ($dataItem['id'] == -1) {
            $item = new User();
        } else {
            $item = $em->getRepository(User::class)
                       ->findOneBy(array('id' => $dataItem['id']));
        }
        
        $item->name = $dataItem['name'];
        
        if ($dataItem['id'] == -1) {
            $em->persist($item);
        }
        
        $em->flush();
        
        return $this->json(array('status' => "OK"));
    }
    
    /**
     * @Route("/users/auth")
     */
    public function authAction(Request $request)
    {
        $id = $request->request->get('id');
        
        $helper = $this->get('facebook.graph')->getRedirectLoginHelper();
        $loginUrl = $helper->getLoginUrl(
            $request->getSchemeAndHttpHost() . $request->getBasePath() . "/users/callback/" . $id,
            array('user_actions.books')
        );
        
        return $this->json(array('status' => "OK", 'url' => $loginUrl));
    }
    
    /**
     * @Route("/users/callback/{id}", defaults={"id": -1})
     */
    public function callbackAction($id, Request $request)
    {
        $fb = $this->get('facebook.graph');
        $logger = $this->get('logger');
        $helper = $fb->getRedirectLoginHelper();
        
        try {
            $accessToken = $helper->getAccessToken($request->getBasePath() . "/users/callback/" . $id);
            
            if (!isset($accessToken)) {
                if ($helper->getError()) {
                    $logger->error(
                        'Error: ' . $helper->getError() . '. Code: ' . $helper->getErrorCode() .
                        '. Reason: ' . $helper->getErrorReason() . '. Description: ' . $helper->getErrorDescription()
                    );
                }
                return false;
            }

            // The OAuth 2.0 client handler helps us manage access tokens
            $oAuth2Client = $fb->getOAuth2Client();

            // Get the access token metadata from /debug_token
            $tokenMetadata = $oAuth2Client->debugToken($accessToken);
            
            // Validation (these will throw FacebookSDKException's when they fail)
            $tokenMetadata->validateExpiration();
            
            if (!$accessToken->isLongLived()) {
                $accessToken = $oAuth2Client->getLongLivedAccessToken($accessToken);
            }
        } catch (FacebookResponseException $e) {
            // When Graph returns an error
            $logger->error('Graph returned an error: ' . $e->getMessage());
            return false;
        } catch (FacebookSDKException $e) {
            // When validation fails or other local issues
            $logger->error('Facebook SDK returned an error: ' . $e->getMessage());
            return false;
        }
        
        $em = $this->getDoctrine()->getManager();
        $item = $em->getRepository(User::class)->findOneBy(array('id' => $id));
        $item->facebookToken = $accessToken;
        $em->flush();
                
        return $this->redirect('/');
    }
}
