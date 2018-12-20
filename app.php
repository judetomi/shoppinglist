<?php

require __DIR__ . '/vendor/autoload.php';

class ShoppingList
{
  private $db;

  /**
   * Start session and stuff
   */
  public function __construct()
  {
    $dotenv = new Dotenv\Dotenv(__DIR__);
    $dotenv->load();
    
    $servername = getenv('SERVERNAME');
    $username = getenv('DBUSER'); 
    $password = getenv('PASSWORD'); 
    $dbname = getenv('DBNAME');

    if (session_status() == PHP_SESSION_NONE) {
      session_start();
    }

    $_SESSION["token"] = md5(uniqid(mt_rand(), true));

    try {
      $this->db = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
      $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch(PDOException $e) {
      // TODO
    }
  }

  /**
   * Get lists from database
   */
  public function getLists()
  {
    $lists = [];

    $query = $this->db->prepare("SELECT * FROM shoplist ORDER BY id");

    $query->execute();
    if($query->rowCount() > 0)
    {
      while($row = $query->fetch(PDO::FETCH_ASSOC))
      {
        $lists[] = [
          'id' => $row['id'],
          'title' => $row['title']
        ];
      }
    }

    return $lists;
  }

  /**
   * Get current list from database
   */
  public function getList()
  {
    $items = [];

    $query = $this->db->prepare("
      SELECT li.* FROM shoplist_items li
      INNER JOIN shoplist l ON l.id = li.list_id
      WHERE l.id=:id
      ORDER BY li.position"
    );

    $data = [
      ':id' => (isset($_POST['id'])) ? $_POST['id'] : 1
    ];

    $query->execute($data);
    if($query->rowCount() > 0)
    {
      while($row = $query->fetch(PDO::FETCH_ASSOC))
      {
        $items[] = [
          'itemid' => $row['id'],
          'title' => $row['title'],
          'description' => $row['description'],
          'qty' => $row['qty'],
          'position' => $row['position']
        ];
      }
    }

    echo json_encode($items);
    exit();
  }

  /**
   * Add new list
   */
  public function addList()
  {
    $option = [];

    $post = array();
    parse_str($_POST['list'], $post);

    $query = $this->db->prepare("
      INSERT INTO shoplist (
        title
      ) VALUES (
        :title
      )
    ");

    $query->bindParam(':title', $post['list_name'], PDO::PARAM_STR);
    if(!empty($post['id'])) {
      $query->bindParam(':id', $post['id'], PDO::PARAM_INT);
    }
    $result = $query->execute();

    if($result)
    {
      $option = [
        'id' => (empty($post['id'])) ? $this->db->lastInsertId() : $post['id'],
        'title' => $post['list_name']
      ];
    }

    echo json_encode($option);
    exit();

  }

  /**
   * Add new item
   */
  public function addItem()
  {
    #if(!$this->validateToken($_POST['csrf'])) return false;

    $item = [];

    $post = array();
    parse_str($_POST['item'], $post);

    if(empty($post['id']))
    {
      $query = $this->db->prepare("
        INSERT INTO shoplist_items (
          list_id,
          title,
          description,
          qty,
          position
        ) VALUES (
          :list_id,
          :title,
          :description,
          :qty,
          :position
        )
      ");
    }
    else
    {
      $query = $this->db->prepare("
        UPDATE shoplist_items SET
          list_id=:list_id,
          title=:title,
          description=:description,
          qty=:qty,
          position=:position
        WHERE id=:id
      ");
    }

    $list_id  = $_POST['list_id'];
    $position = $_POST['position'];

    $query->bindParam(':list_id', $list_id, PDO::PARAM_INT);
    $query->bindParam(':title', $post['item_name'], PDO::PARAM_STR);
    $query->bindParam(':description', $post['item_desc'], PDO::PARAM_STR);
    $query->bindParam(':qty', $post['item_qty'], PDO::PARAM_STR);
    $query->bindParam(':position', $position, PDO::PARAM_INT);
    if(!empty($post['id'])) {
      $query->bindParam(':id', $post['id'], PDO::PARAM_INT);
    }
    $result = $query->execute();

    if($result)
    {
      $item = [
        'itemid' => (empty($post['id'])) ? $this->db->lastInsertId() : $post['id'],
        'title' => $post['item_name'],
        'description' => $post['item_desc'],
        'qty' => $post['item_qty'],
        'position' => $position,
        'id' => $post['id']
      ];
    }

    echo json_encode($item);
    exit();
  }

  /**
   * Delete item
   */
  public function deleteItem()
  {
    $query = $this->db->prepare("DELETE FROM shoplist_items WHERE id=:id AND list_id=:list_id");
    $data = [
      ':id' => $_POST['id'],
      ':list_id' => $_POST['list_id']
    ];

    $result = $query->execute($data);

    echo json_encode(['result' => $result]);
    exit();
  }

  /**
   * Delete list
   */
  public function deleteList()
  {
    $query = $this->db->prepare("DELETE FROM shoplist WHERE id=:list_id");
    $data = [
      ':list_id' => $_POST['list_id']
    ];

    $result = $query->execute($data);
    if($result)
    {
      $query = $this->db->prepare("DELETE FROM shoplist_items WHERE list_id=:list_id");
      $data = [
        ':list_id' => $_POST['list_id']
      ];

      $result = $query->execute($data);
    }

    echo json_encode(['result' => $result, 'id' => $_POST['list_id']]);
    exit();
  }

  /**
   * Clear all list items
   */
  public function clearList()
  {
    $query = $this->db->prepare("DELETE FROM shoplist_items WHERE list_id=:id");
    $data = [
      ':id' =>  $_POST['list_id']
    ];

    $result = $query->execute($data);

    echo json_encode(['result' => $result]);
    exit();
  }

  /**
   * Update position
   */
  public function updatePosition()
  {
    if(isset($_POST['data']))
    {
      $i = 0;
      foreach($_POST['data'] as $item)
      {
        $query = $this->db->prepare("UPDATE shoplist_items SET position=:position WHERE id=:id");
        $data = [
          ':position' => $i,
          ':id' => $item['id']
        ];

        $result = $query->execute($data);

        $i++;
      }

      echo json_encode(['result' => $result]);
      exit();
    }
  }
  
  /**
   * Import items
   */
  public function importListItems()
  {
    parse_str($_POST['items'], $post);
    
    $list_id  = $_POST['list_id'];
    $position = $_POST['position'];
    
    $result = $this->parseList($post);
    
    $items = [];
    
    if($result)
    {
      $i = $position;
      
      foreach($result as $row)
      {
        $items[] = [
          'itemid' => $i,
          'title' => $row['qty'] .  ' ' . $row['title'],
          'description' => "",
          'qty' => $row['qty'],
          'position' => $i
        ];
        
        $i++;
      }
    }

    echo json_encode($items);
    exit();
  }

  /**
   * Validate session token
   */
  private function validateToken($token)
  {
    if($oken == $_SESSION["token"]) {
      $_SESSION = array();
      session_destroy();
      return true;
    }

    return false;
  }

  /**
   * Get Next id from the database
   */
  private function getMaxID()
  {
    $query = $this->db->prepare("SELECT count(*) as counter FROM shoplist");

    $query->execute();

    if($query->rowCount() > 0)
    {
      $row = $query->fetch(PDO::FETCH_ASSOC);
      $result = $row['counter'] + 1;

      return $result;
    }
    else
    {
      return 1;
    }
  }
  
  /**
   * List parser 
   */
  private function parseList($rows)
  {
    $mitat = [
      'rs',
      'ps',
      'pss',
      'kpl',
      'rkl',
      'prk',
      'tlk',
      'tl',
      'g',
      'kg',
      'dl',
      'ml',
      'cup',
      'quart',
      'tblsp',
      'tsp',
      'oz',
      'lb'
    ];
    
    if(isset($rows)) 
    {
      $r = [];
    
      foreach ($rows as $key => $row) 
      {
        $found = 0;
        foreach($mitat as $mitta)
        {
          if (strpos($row, $mitta) !== false) 
          {
            $found = 1;
            $maara = strstr($row, $mitta, true);
            $title = strstr($row, $mitta);
            $r[] = [
              'qty' => trim($maara),
              'title' => preg_replace('/\s{1,}/', ' ', $title)
            ];
            
            break;
          }
        }
        
        if($found == 0)
        {
          if (strpos($row, ' ') !== false)
          {
            $temp = explode(' ', trim($row));
            $title = $temp[1];
            if(sizeof($temp) > 2) {
              for($i = 2; $i < sizeof($temp); $i++)
              {
                $title .= ' ' . $temp[$i];
              }
            }
            $r[] = [
              'qty' => $temp[0],
              'title' => preg_replace('/\s{1,}/', ' ', $title)
            ];
            
          } 
        }
        
      }
      
      return $r;
    }
    
    return false;
  }
}
