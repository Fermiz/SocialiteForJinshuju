<?php

namespace Laravel\Socialite\Two;

use DB;
use Carbon\Carbon;

class JinshujuProvider extends AbstractProvider implements ProviderInterface
{
    /**
     * The separating character for the requested scopes.
     *
     * @var string
     */
    protected $scopeSeparator = ' ';

    /**
     * The scopes being requested.
     *
     * @var array
     */
    protected $scopes = [
        'public',
        'forms',
        'read_entries',
        'form_setting',
    ];
    
    /**
     * The user fields being requested.
     *
     * @var array
     */
    protected $fields = ['email', 'nickname', 'avatar', 'paid'];

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase($this->authUrl."/oauth/authorize", $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return $this->authUrl."/oauth/token";
    }

    /**
     * Get the POST fields for the token request.
     *
     * @param  string  $code
     * @return array
     */
    protected function getTokenFields($code)
    {
        return array_add(
            parent::getTokenFields($code), 'grant_type', 'authorization_code'
        );
    }
    
    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $userUrl = $this->apiUrl."/v4/me?access_token=".$token;
 
        try {
            $response = $this->getHttpClient()->get($userUrl, $this->getRequestOptions());
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            abort($e->getResponse()->getStatusCode());
        }

        // $paid = array_get(json_decode($response->getBody(), true), 'paid');
        // if($paid == false){
        //     abort(402);
        // }

        return json_decode($response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User)->setRaw($user)->map([
            'email' => array_get($user, 'email'), 'nickname' => array_get($user, 'nickname'),
            'avatar' => array_get($user, 'avatar'),'paid' => array_get($user, 'paid')
        ]);
    }

    /**
     * Get the default options for an HTTP request.
     *
     * @return array
     */
    protected function getRequestOptions()
    {
        return [
            'headers' => [
                'Accept' => 'application/json',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFormByToken($token)
    {
        $formUrl = $this->apiUrl."/v4/forms?access_token=".$token;
        
        try {
            $response = $this->getHttpClient()->get($formUrl, $this->getRequestOptions());
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            abort($e->getResponse()->getStatusCode());
        }
        
        //分页获取处理
        $next = $response->getHeader('Link');
        
        $result = $response->getBody();

        if(@strpos($next[0],"next")!==false){
           $a = (strrpos($next[0],"<"));
           $b = (strrpos($next[0],">"));
           $match = substr($next[0],$a+1,$b-1);

           try {
                $response = $this->getHttpClient()->get($match, $this->getRequestOptions());
           } catch (\GuzzleHttp\Exception\ClientException $e) {
                abort($e->getResponse()->getStatusCode());
           }

           $temp = $response->getBody();
           $next = $response->getHeader('Link');

           $result = json_encode(
              array_merge(json_decode($result,true),json_decode($temp, true))
            );

           while(@strpos($next[0],",")!==false){
               $str = explode(', ', $next[0]);
               $a = (strripos($str[1],"<"));
               $b = (strripos($str[1],">"));
               $match = substr($str[1],$a+1,$b-1);

               try {
                    $response = $this->getHttpClient()->get($match, $this->getRequestOptions());
               } catch (\GuzzleHttp\Exception\ClientException $e) {
                    abort($e->getResponse()->getStatusCode());
               }

               $temp = $response->getBody();
               $next = $response->getHeader('Link');

               $result = json_encode(
                  array_merge(json_decode($result,true),json_decode($temp, true))
                );

           }
           
        }

        return json_decode($result , true);
    }

    /**
     * {@inheritdoc}
     */
    public function getFeildByToken($form,$token)
    {
        $detailUrl = $this->apiUrl."/v4/forms/".$form.'?access_token='.$token;
          
        try {
           $response = $this->getHttpClient()->get($detailUrl, $this->getRequestOptions());
        } catch (\GuzzleHttp\Exception\ClientException $e) {
           
           if($e->getResponse()->getStatusCode() == 404){
              $arr = array(); 
              return $arr;
           }else{
              abort($e->getResponse()->getStatusCode());
           }
        }
        
        //分页获取处理
        $next = $response->getHeader('Link');
        
        $result = $response->getBody();

        if(@strpos($next[0],"next")!==false){
           $a = (strrpos($next[0],"<"));
           $b = (strrpos($next[0],">"));
           $match = substr($next[0],$a+1,$b-1);
           
           try {
                $response = $this->getHttpClient()->get($match, $this->getRequestOptions());
           } catch (\GuzzleHttp\Exception\ClientException $e) {
                abort($e->getResponse()->getStatusCode());
           }

           $temp = $response->getBody();
           $next = $response->getHeader('Link');

           $result = json_encode(
              array_merge(json_decode($result,true),json_decode($temp, true))
            );

           while(@strpos($next[0],",")!==false){
               $str = explode(', ', $next[0]);
               $a = (strripos($str[1],"<"));
               $b = (strripos($str[1],">"));
               $match = substr($str[1],$a+1,$b-1);

               try {
                    $response = $this->getHttpClient()->get($match, $this->getRequestOptions());
               } catch (\GuzzleHttp\Exception\ClientException $e) {
                    abort($e->getResponse()->getStatusCode());
               }

               $temp = $response->getBody();
               $next = $response->getHeader('Link');

               $result = json_encode(
                  array_merge(json_decode($result,true),json_decode($temp, true))
                );

           }
           
        }

        $select = json_decode($result, true);
        
        $output = json_encode($select['fields']);

        return json_decode($output, true);
    }

    /**
     * {@inheritdoc}
     */
    public function getDataByToken($form,$token)
    {
        $dataUrl = $this->apiUrl."/v4/forms/".$form.'/entries?access_token='.$token;

        try {
            $response = $this->getHttpClient()->get($dataUrl, $this->getRequestOptions());
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            abort($e->getResponse()->getStatusCode());
        }

        //分页获取处理
        $next = $response->getHeader('Link');
        
        $result = $response->getBody();

        if(@strpos($next[0],"next")!==false){
           $a = (strrpos($next[0],"<"));
           $b = (strrpos($next[0],">"));
           $match = substr($next[0],$a+1,$b-1);
           
           try {
                $response = $this->getHttpClient()->get($match, $this->getRequestOptions());
           } catch (\GuzzleHttp\Exception\ClientException $e) {
                abort($e->getResponse()->getStatusCode());
           }

           $temp = $response->getBody();
           $next = $response->getHeader('Link');

           $result = json_encode(
              array_merge(json_decode($result,true),json_decode($temp, true))
            );

           while(@strpos($next[0],",")!==false){
               $str = explode(', ', $next[0]);
               $a = (strripos($str[1],"<"));
               $b = (strripos($str[1],">"));
               $match = substr($str[1],$a+1,$b-1);

               try {
                    $response = $this->getHttpClient()->get($match, $this->getRequestOptions());
               } catch (\GuzzleHttp\Exception\ClientException $e) {
                    abort($e->getResponse()->getStatusCode());
               }

               $temp = $response->getBody();
               $next = $response->getHeader('Link');

               $result = json_encode(
                  array_merge(json_decode($result,true),json_decode($temp, true))
                );

           }
           
        }

        return json_decode($result, true);
    }
    
    /**
     * {@inheritdoc}
     */
    public function user()
    {

            // if ($this->hasInvalidState()) {
            //      abort(401);
            //      //throw new InvalidStateException;                
            // }

            try {
                $response = $this->getAccessTokenResponse($this->getCode());
            } catch (\GuzzleHttp\Exception\ClientException $e) {
                abort($e->getResponse()->getStatusCode());
            }

            $user = $this->mapUserToObject($this->getUserByToken(
               $token = array_get($response, 'access_token')
            ));
            
            $refreshToken = array_get($response, 'refresh_token');
            $expires = array_get($response, 'expires_in') + array_get($response, 'created_at') - 720;

            date_default_timezone_set('Asia/Shanghai');
            $current_time = Carbon::now();

            $avatar = $user->getAvatar();
            if( $avatar == null){
                $avatar = "https://gd-prod-assets.b0.upaiyun.com/assets/avatar_default-1ef06a094c3f62c55d221b402a0f6f10.png";
            }

            $search = DB::table('users')->where([
                                'email' => $user->getEmail()
                                ])->count();
            if($search == 0){

              DB::table('users')->insert(
                          [ 
                            'email' => $user->getEmail(),
                            'status' => 0,
                            'nickname' => $user->getNickname(),
                            'avatar' => $avatar,
                            'access_token' => $token, 
                            'refresh_token' => $refreshToken,  
                            'expires_in' => $expires, 
                            'created_at' => $current_time,
                            'updated_at' => $current_time 
                          ]
                          );

            }else{
              DB::table('users')        
                   ->where([
                            'email' => $user->getEmail()
                           ])
                   ->update([
                            'nickname' => $user->getNickname(),
                            'avatar' => $avatar,
                            'access_token' => $token, 
                            'refresh_token' => $refreshToken,  
                            'expires_in' => $expires,
                            'updated_at' => $current_time
                          ]);
            }


            session(['email'=> $user->getEmail()]);
            
            return $user->setToken($token)
                        ->setRefreshToken($refreshToken)
                        ->setExpiresIn($expires);

    }

    /**
     * {@inheritdoc}
     */
    public function refresh()
    {
        if(\Session::has('email')){
            $email= session('email');
        }else{
            return redirect("/");
        }

        $nowuser = DB::table('users')->where('email',$email)
                                  ->first();

        $refreshToken = $nowuser->refresh_token;
        $ExpiresIn = $nowuser->expires_in;
        
        date_default_timezone_set('Asia/Shanghai');
        $now = Carbon::now();

        if (strtotime($now) >= $ExpiresIn){ 

            try {
                $response = $this->getAccessTokenRefresh($refreshToken);
            } catch (\GuzzleHttp\Exception\ClientException $e) {
                abort($e->getResponse()->getStatusCode());
            }
            
            $user = $this->mapUserToObject($this->getUserByToken(
                $token = array_get($response, 'access_token')
            ));
                       
            $refreshtoken = array_get($response, 'refresh_token');
            $expires = array_get($response, 'expires_in') + array_get($response, 'created_at') - 720;

            $current_time = Carbon::now();

            DB::table('users')
                         ->where('email',$email)
                         ->update([ 
                                  'access_token' => $token, 
                                  'refresh_token' => $refreshtoken,  
                                  'expires_in' => $expires, 
                                  'updated_at' => $current_time
                                ]);

        }

    }

    /**
     * {@inheritdoc}
     */
    public function update($form,$redirect,$rectfields)
    {
        if(\Session::has('email')){
            $email= session('email');
        }else{
            return redirect("/");
        }

        $user = DB::table('users')->where('email',$email)
                                  ->first();

        $access = $user->access_token;

        $this->sendUpdateSettings($form,$access,$redirect,$rectfields);

    }

    /**
     * {@inheritdoc}
     */
    public function submitValidate($fieldinfo,$jamr_h)
    {
        $validUrl = env('JINSHUJU_URL', 'https://jinshuju.net').'/api/v1/jamr_v?'.$fieldinfo.'&jamr_h='.$jamr_h;
         
        try {
            $response = $this->getHttpClient()->get($validUrl, $this->getRequestOptions());
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            abort($e->getResponse()->getStatusCode());
        }
        
        $valid = array_get($response, 'status');

        if ($valid == "SUCCESS"){
            return true;
        }else{
            return false;
        }       

    }

     /**
     * {@inheritdoc}
     */
    public function autorefresh()
    {   
        date_default_timezone_set('Asia/Shanghai');
        $now = Carbon::now();     
        $users = DB::table('users')->orderBy('id','ASC')
                                   ->where('expires_in','<=', strtotime($now))
                                   ->get();

        //$users = DB::table('users')->get();

        foreach ($users as $user) {

          $refreshToken = $user->refresh_token;

          $response = $this->getAccessTokenRefresh($refreshToken);

          $nowuser = $this->mapUserToObject($this->getUserByToken(
              $token = array_get($response, 'access_token')
          ));
                     
          $refreshtoken = array_get($response, 'refresh_token');
          $expires = array_get($response, 'expires_in') + array_get($response, 'created_at') - 720;

          //date_default_timezone_set('Asia/Shanghai');
          $current_time = Carbon::now();

          DB::table('users')->where('id',$user->id)
                            ->update([ 
                                      'access_token' => $token, 
                                      'refresh_token' => $refreshtoken,  
                                      'expires_in' => $expires,
                                      'updated_at' => $current_time
                                    ]);

        }

    }


}
