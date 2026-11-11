-- =============================================
-- Base de données GreenBite Restaurant - VERSION FINALE
-- =============================================

CREATE DATABASE
IF
  NOT EXISTS greenbite_db CHARACTER
  SET utf8mb4 COLLATE utf8mb4_unicode_ci;
  USE greenbite_db;

  -- =============================================
  -- Table des plats et boissons
  -- =============================================
  DROP TABLE
  IF
    EXISTS plats;
    CREATE TABLE plats (
      id INT AUTO_INCREMENT PRIMARY KEY
      , name VARCHAR(255) NOT NULL
      , category VARCHAR(50) NOT NULL
      , description TEXT
      , price DECIMAL(10, 2) NOT NULL
      , image VARCHAR(500)
      , sans_gluten BOOLEAN DEFAULT FALSE
      , sans_lactose BOOLEAN DEFAULT FALSE
      , bio BOOLEAN DEFAULT FALSE
      , supplements JSON
      , accompagnements JSON
      , created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      , INDEX idx_category (category)
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

    -- =============================================
    -- Table des commandes
    -- =============================================
    DROP TABLE
    IF
      EXISTS commandes;
      CREATE TABLE commandes (
        id INT AUTO_INCREMENT PRIMARY KEY
        , client_name VARCHAR(255) NOT NULL
        , client_email VARCHAR(255)
        , client_phone VARCHAR(50)
        , items JSON NOT NULL
        , total DECIMAL(10, 2) NOT NULL
        , status VARCHAR(50) DEFAULT 'en_attente'
        , notes TEXT
        , created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        , updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON
        UPDATE
          CURRENT_TIMESTAMP
          , INDEX idx_status (status)
          , INDEX idx_created (created_at)
      ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

      -- =============================================
      -- Table admin
      -- =============================================
      DROP TABLE
      IF
        EXISTS admins;
        CREATE TABLE admins (
          id INT AUTO_INCREMENT PRIMARY KEY
          , username VARCHAR(100) NOT NULL UNIQUE
          , password VARCHAR(255) NOT NULL
          , email VARCHAR(255)
          , created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

        -- =============================================
        -- Admins - CORRIGÉ
        -- =============================================
        INSERT INTO
          admins (username, password, email)
        VALUES
          (
            'admin'
            , '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
            , 'admin@greenbite.tn'
          )
          , (
            'meriem'
            , '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
            , 'meriemabdelkader26@gmail.com'
          );

        -- =============================================
        -- Plats avec VOS VRAIES IMAGES
        -- =============================================
        INSERT INTO
          plats (
            name
            , category
            , description
            , price
            , image
            , sans_gluten
            , sans_lactose
            , bio
            , supplements
            , accompagnements
          )
        VALUES
          -- PLATS
          (
            'Poulet grillé aux légumes'
            , 'plats'
            , 'Poulet grillé accompagné de légumes frais et riz'
            , 20.00
            , 'images/plat1.jpg'
            , TRUE
            , TRUE
            , TRUE
            , '[{"name":"Fromage sans lactose","price":3},{"name":"Avocat","price":4},{"name":"Légumes grillés","price":2}]'
            , '[{"name":"Riz","price":0},{"name":"Quinoa","price":2},{"name":"Légumes vapeur","price":0}]'
          )
          , (
            'Bol de Riz au Poulet & Courgettes'
            , 'plats'
            , 'Bol équilibré avec du riz, poulet grillé et courgettes'
            , 27.00
            , 'images/plat2.jpg'
            , TRUE
            , TRUE
            , FALSE
            , '[{"name":"Fromage sans lactose","price":3},{"name":"Avocat","price":4},{"name":"Sauce tahini","price":2}]'
            , '[{"name":"Riz complet","price":0},{"name":"Quinoa","price":2},{"name":"Salade verte","price":0}]'
          )
          , (
            'Veggie Burger'
            , 'plats'
            , 'Burger végétarien avec steak de légumes et sauce maison'
            , 20.00
            , 'images/plat3.jpg'
            , TRUE
            , FALSE
            , TRUE
            , '[{"name":"Fromage vegan","price":3},{"name":"Avocat","price":4},{"name":"Bacon végétal","price":3}]'
            , '[{"name":"Frites maison","price":0},{"name":"Salade","price":0},{"name":"Patates douces","price":2}]'
          )
          , (
            'Pâtes au pesto saumon'
            , 'plats'
            , 'Pâtes fraîches au pesto maison et saumon grillé'
            , 38.00
            , 'images/plat4.jpg'
            , TRUE
            , FALSE
            , FALSE
            , '[{"name":"Parmesan","price":2},{"name":"Tomates cerises","price":2},{"name":"Roquette","price":1}]'
            , '[{"name":"Pain à l\'ail","price":0},{"name":"Salade césar","price":3}]'
          )
          , (
            'Salade Complète'
            , 'plats'
            , 'Grande salade fraîche avec poulet, avocat et vinaigrette'
            , 28.00
            , 'images/plat5.jpg'
            , TRUE
            , TRUE
            , TRUE
            , '[{"name":"Fromage de chèvre","price":3},{"name":"Noix","price":2},{"name":"Poulet grillé","price":5}]'
            , '[{"name":"Pain complet","price":0},{"name":"Crackers","price":1}]'
          )
          , (
            'Buddha Bowl'
            , 'plats'
            , 'Bol complet avec quinoa, légumes rôtis et houmous'
            , 25.00
            , 'images/plat6.jpg'
            , TRUE
            , TRUE
            , TRUE
            , '[{"name":"Avocat","price":4},{"name":"Feta","price":3},{"name":"Falafels","price":4}]'
            , '[{"name":"Quinoa","price":0},{"name":"Riz brun","price":0},{"name":"Houmous extra","price":2}]'
          )
          , (
            'Poké Bowl Saumon'
            , 'plats'
            , 'Bowl hawaïen avec saumon cru, riz et légumes'
            , 32.00
            , 'images/plat7.jpg'
            , TRUE
            , TRUE
            , FALSE
            , '[{"name":"Avocat","price":4},{"name":"Edamame","price":3},{"name":"Mangue","price":3}]'
            , '[{"name":"Riz sushi","price":0},{"name":"Salade wakame","price":2}]'
          )
          , (
            'Curry de Légumes'
            , 'plats'
            , 'Curry végétarien épicé avec lait de coco'
            , 22.00
            , 'images/plat8.jpg'
            , TRUE
            , TRUE
            , TRUE
            , '[{"name":"Tofu","price":3},{"name":"Pois chiches","price":2},{"name":"Épinards","price":1}]'
            , '[{"name":"Riz basmati","price":0},{"name":"Naan","price":2},{"name":"Riz au coriandre","price":1}]'
          )
          , -- BOISSONS
            (
            'Jus Détox Vert'
            , 'boissons'
            , 'Jus frais de pomme, concombre, épinards et citron'
            , 17.00
            , 'images/boisson1.jpg'
            , TRUE
            , TRUE
            , TRUE
            , '[]'
            , '[]'
          )
          , (
            'Limonade Maison'
            , 'boissons'
            , 'Limonade artisanale au citron et menthe fraîche'
            , 15.00
            , 'images/boisson2.jpg'
            , TRUE
            , TRUE
            , FALSE
            , '[]'
            , '[]'
          )
          , (
            'Thé Glacé Bio'
            , 'boissons'
            , 'Thé glacé bio parfumé aux fruits'
            , 12.00
            , 'images/boisson3.jpg'
            , TRUE
            , TRUE
            , TRUE
            , '[]'
            , '[]'
          )
          , (
            'Eau Détox'
            , 'boissons'
            , 'Eau infusée aux fruits et herbes fraîches'
            , 10.00
            , 'images/boisson4.jpg'
            , TRUE
            , TRUE
            , TRUE
            , '[]'
            , '[]'
          )
          , -- DESSERTS (réutilisant des images)
            (
            'Smoothie Bowl Açaï'
            , 'desserts'
            , 'Smoothie bowl aux baies d\'açaï et fruits frais'
            , 12.00
            , 'images/plat10.jpg'
            , TRUE
            , TRUE
            , TRUE
            , '[]'
            , '[]'
          )
          , (
            'Chia Pudding'
            , 'desserts'
            , 'Pudding de graines de chia avec fruits frais'
            , 10.00
            , 'images/plat9.jpg'
            , TRUE
            , TRUE
            , TRUE
            , '[]'
            , '[]'
          )
          , (
            'Energy Balls'
            , 'desserts'
            , 'Boules d\'énergie aux dattes et noix'
            , 17.00
            , 'images/plat11.jpg'
            , TRUE
            , TRUE
            , TRUE
            , '[]'
            , '[]'
          );

        -- Succès !
        SELECT
          '✅ Base de données créée avec succès!' AS Message;
        SELECT
          CONCAT('📊 ', COUNT(*), ' plats') AS Total
        FROM
          plats;
        SELECT
          CONCAT('👤 Admins: admin/admin123 et meriem/admin123') AS Connexion;