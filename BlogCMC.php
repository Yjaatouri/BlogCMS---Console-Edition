<?php 
    class article {
        private int $id_article;
        private string $titre;
        private string $content;
        private string $excerpt;
        private string $status;
        private string $author;
        private string $createdAtA;
        private DateTime $publishedAtA;
        private DateTime $updatedAtA;
        function __construct($id_article,$titre,$content,$excerpt, $status,$author,$createdAtA,$publishedAtA,$updatedAtA)
        {
            $this->id_article = $id_article;
            $this->titre = $titre;
            $this->content = $content;
            $this->excerpt = $excerpt;
            $this->status = $status;
            $this->author = $author;
            $this->createdAtA = $createdAtA;
            $this->publishedAtA = $publishedAtA;
            $this->updatedAtA = $updatedAtA;
        }
        public function addCategory (){

        }
        public function removeCategorie(){

        }
        public function getComments(){

        }
    }
        class comment {
        private int $id_comment ;
        private string $content;
        private DateTime $dateC;
        function __construct($id_comment,$content,$dateC)
        {
            $this->id_comment = $id_comment;

            $this->content = $content;
            $this->dateC = $dateC;
        }
        

    }
        class categorie {
        private int $id_categorie;
        private string $nameCa;
        private string $description;
        private DateTime $dateCA;
        function __construct($id_categorie, $nameCa ,$description ,$dateCA)
        {
         $this->id_categorie = $id_categorie;
         $this->nameCa = $nameCa;
         $this->description = $description;
         $this->dateCA = $dateCA;

        }
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
    function __construct($id_user , $username , $email , $password , $createdAT , $lastLogin)
    {
        $this->id_user = $id_user;
        $this->username = $username;
        $this->email = $email;
        $this->password  = $password;
        $this->createdAT = $createdAT;
        $this->lastLogin = $lastLogin;
    }
    public function login($email , $password){

    }
    public function logout(){

    }
}
class moderateur extends user {
    public function approvComment($id_comment){
        
    }
    public function deleteCommnet($id_comment){

    }
    public function createCategory($nameCa){

    } 
    public function deleteCategorie($id_categorie){

    }
    public function publishArticle($id_article){

    }
    public function deleteAnyArticle($id_article){

    }
    
}
class author extends user {
     private string $bio;
     function __construct($bio){
        $this->bio = $bio;
     }
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
        function __construct($isSuperAdmin){
            $this->isSuperAdmin = $isSuperAdmin;

        }
        public function createUser($username , $email ,$password){

        }
        public function deleteUser($id_user){

        }
        public function updateUserRole($id_user){

        }
        public function listAllUsers (){

        }
    }
    class editeur extends moderateur {
        private string $moderationLevel;
    }
?>