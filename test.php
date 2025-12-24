<?php
class User {
    protected int $id_user;
    protected string $username;
    protected string $email;
    protected string $role;
    protected string $password;

    public function __construct(string $username,string $email)
    {
        $this->username = $username;
        $this->email = $email;
        
    }

    public function afficherInfo()
    {
        return "user:  $this->username email: $this->email";
    }


}
$user1 = new User("john", "john@mail.com","admin");


$userAuteur = new User("lea", "lea@blog.com", "auteur");
$userVisiteur = new User("visiteur", "v@blog.com", "visiteur");


class Article {
    protected string $title;
    protected string $content;
    protected string $statu;

    public function __construct(string $title,string $content) 
    {
        $this->title = $title;
        $this->content = $content;
        $this->statu = "brouillon";
    }
    public function afficher()
    {
        return "title : $this->title content : $this->content statu : $this->statu \n";  
    }

    public function toggleStatus()
    {
        $this->statu = "public";
        return;
    }

}
$article = new Article("the world", "the world is happy");
echo $article->afficher();
echo $article->toggleStatus();
echo $article->afficher();