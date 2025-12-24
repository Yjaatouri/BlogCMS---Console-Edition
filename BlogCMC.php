<?php 
    class article {
        private int $id_article;
        private string $titre;
        private string $content;
        private string $excerpt;
        private string $status;
        private string $author;
        private string $createdAtA;
        private DateTime $publishedAt;
        private DateTime $updatedAtA;
        public function addCategory (){

        }
        public function removeCategorie(){

        }
        public function getComments(){

        }
    }
        class comment {
        private string $id_comment ;
        private string $libelle;
        private string $description;
        private DateTime $dateC;

    }
        class categorie {
        private int $id_categorie;
        private string $name;
        private string $description;
        private DateTime $dateCA;
        public function getParent(){

        }
        public function getTree(){

        }
    }
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
    class admin extends moderateur {
        private bool $isSuperAdmin;
        public function createUser(){

        }
        public function deleteUser(){

        }
        public function updateUserRole(){

        }
        public function listAllUsers (){

        }
    }
    class editeur extends moderateur {
        private string $moderationLevel;
    }



?>
