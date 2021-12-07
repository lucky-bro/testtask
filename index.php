<?php
$json = file_get_contents('https://gist.githubusercontent.com/cristiberceanu/94c1539c9bd7cc0f2e3e6e12a26c1551/raw/771417ba472bf1e7c213b6684656be95898892d6/books-data-source.json');
$data = json_decode($json, true); 

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

CONST HOST = "localhost";
CONST USER = "root";
CONST PASS = "a,yi0ldPwpPe";
CONST DB = "testtask";

function dbConnection() {
    return new mysqli(HOST, USER, PASS, DB);
}

function clearDb() {
    $db = dbConnection();
    $db->query('TRUNCATE `testtask`.`books`');
    $db->query('TRUNCATE `testtask`.`authors`');
    $db->query('TRUNCATE `testtask`.`author-book`');
    $db->close();
}

function insertData($data) {
    $authors = [];
    $db = dbConnection();
    $bookStmt = $db->prepare("INSERT INTO books ( `title`, `thumbnailUrl`, `status`) VALUES (?, ?, ?)");
    $authorStmt = $db->prepare("INSERT INTO authors ( `author`) VALUES (?)");
    $bookAuthorStmt = $db->prepare("INSERT INTO `author-book` ( `book_id`, `author_id`) VALUES (?, ?)");
    
    foreach($data as $key => $book) {

        $bookStmt->bind_param("sss", $book['title'], $book['thumbnailUrl'], $book['status'] );
        $bookStmt->execute();
        $book_id = $bookStmt->insert_id;

        foreach($book['authors'] as $author) {
            $author_id = '';
            if(!empty($author)) {
                if(!in_array($author, $authors)) {
                    $authorStmt->bind_param("s", $author);
                    $authorStmt->execute();
                    $author_id = $authorStmt->insert_id;
                    $authors[$author_id] = $author;
                }
                else {
                    $author_id = array_search($author, $authors);
                }

                if(!empty($author_id) && !empty($book_id)) {
                    $bookAuthorStmt->bind_param("ii", $book_id , $author_id);
                    $bookAuthorStmt->execute();
                }

            }
        }
    }

    echo 'Books count: '.count($data);
    echo '<br>Authors count: '.count($authors).'<br>';

    $authorStmt->close();
    $bookAuthorStmt->close();
    $bookStmt->close();
    $db->close();
}

function showTopAuthors() {
    $db = dbConnection();
    $selectStmt = $db->query("SELECT t1.author_id, t2.author, t2.id, COUNT(t1.author_id) FROM `author-book` t1 INNER JOIN authors t2 ON t1.author_id=t2.id GROUP BY t1.author_id ORDER BY COUNT(t1.author_id) DESC LIMIT 0, 3");
    $rows = $selectStmt->fetch_all(MYSQLI_ASSOC);
    
    if(!empty($rows)) {
        echo '<br>Top 3 authors:<br>';
        foreach ($rows as $key => $row) {
            echo ++$key.'. '.$row['author'].', number of books: '.$row["COUNT(t1.author_id)"].'<br>';
        }
    }
    $db->close();
}

clearDb();
insertData($data);
showTopAuthors();

?>