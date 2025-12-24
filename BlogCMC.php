<?php 
class user {
    protected int $id_user;
    protected string $username;
    protected string $email;
    protected string $password;
    protected DateTime $createdAT; 
    protected DateTime $lastLogin;

    public function login($email , $password){

    }
    public function logout(){

    }

}
class moderateur extends user {
    public function approvComment(){
        
    }
    public function deleteCommnet(){

    }
    public function createCategory(){

    } 
    public function publishArticle(){

    }
    public function deleteAnyArticle(){

    }
    
}
class author extends user {
     private string $bio;
     public function createArticle(){

     }
     public function updateOwnArticle(){

     }
     public function deleteOwnArticle(){

     }
     public function getMyArticle(){

     }
    }
    class editeur extends moderateur {
        private string $moderationLevel;
        
    }
?>
