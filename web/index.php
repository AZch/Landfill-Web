<?php
    // web/index.php
    require_once __DIR__.'/../vendor/autoload.php';
    use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
    use Symfony\Component\HttpFoundation\Request;
    use Symfony\Component\HttpFoundation\Response;

    Request::enableHttpMethodParameterOverride();

    $app = new Silex\Application();
    $app['debug'] = true;

    $app->register(new Silex\Provider\TwigServiceProvider(),
            ['twig.path' => __DIR__ . '/../view']);
    $app->register(new Silex\Provider\DoctrineServiceProvider(),
            ['db.options' => ['driver' => 'pdo_mysql', 'dbname' => 'LandfillsDB', 'charset' => 'utf8']]);

    function photos($photoLoc) {
        $allowedTypes = array("jpg", "png", "gif");
        #echo $photoLoc;
        $resPhotos = array();
        $fileParts = array();
        $ext = "";
        $title = "";
        $i = 0;
        if ($dirHandle = @opendir($photoLoc)) {
            while ($file = readdir($dirHandle)) {
                if ($file == "." || $file == "..")
                    continue;
                $fileParts = explode(".", $file);
                $ext = strtolower(array_pop($fileParts));

                if (in_array($ext, $allowedTypes)) {
                    array_push($resPhotos, $photoLoc.$file);
                    $i++;
                }
            }
            closedir($dirHandle);
        }
        return $resPhotos;
    }

    function index($admin) {
        global $app;

        $conn = $app['db'];
        $landfill = $conn->fetchAll(
            'select lf.`name`, lf.`dateFind`, lf.`dateStatement`, lf.`id`, lf.`photoLocation` photos, lf.`location` from `landfill` lf'
        );
        for ($i = 0; $i < sizeof($landfill); $i++) {
            $landfill[$i]['photos'] = photos($landfill[$i]['photos']);
            #debug_to_console($landfill[$i]['photos']);
        }
        return $app['twig']->render('index.twig', ['landfill' => $landfill, 'admin' => $admin]);
    }

    $app->get('/', function() use($app) {
        return index(false);
    });

    $app->get('/admin', function() use($app) {
        return index(true);
    });

    $app->get('/landfill/{id}', function($id) use($app) {
        $id = (int) $id;

        $conn = $app['db'];
        $info = $conn->fetchAssoc(
            'select lf.`name`, lf.`dateFind`, lf.`dateStatement`, lf.`id`, lf.`photoLocation` photos, lf.`location`, 
            lf.`name` vols, lf.`name` events 
             from `landfill` lf
             where lf.`id` = ?', [$id]
        );

        if (!$info) throw new NotFoundHttpException("Свалка $id не обнаружена!");
        $info['photos'] = photos($info['photos']);
        $info['vols'] = $conn->fetchAll(
            'SELECT vol.`dateVolunteers`, vol.`countVolunteers`, vol.`description`, vol.`id`
             FROM `volunteers` vol 
             WHERE vol.`idLandfillVolunt` = ?', [$id]
        );
        $info['events'] = $conn->fetchAll(
            'select ev.`dateEvents`, ev.`id`, ev.`nameEvent`, ev.`description`
             from `events` ev 
             where ev.`idLandfillEvent` = ?', [$id]
        );

        return $app['twig']->render('openDump.twig', ['info' => $info, 'admin' => false]);
    });

    function form($id) {
        debug_to_console('priv');
        global $app;
        $conn = $app['db'];

        $data = array(
            'edit' => true
        );

        if ($id != false) {
            debug_to_console('priv2');
            $conn = $app['db'];
            $info = $conn->fetchAssoc(
                'select lf.`name`, lf.`dateFind`, lf.`dateStatement`, lf.`id`, lf.`photoLocation` photos, lf.`location`, 
                  lf.`name` vols, lf.`name` events 
                  from `landfill` lf
                  where lf.`id` = ?', [$id]
            );
            debug_to_console('priv3');
            if (!$info) throw new NotFoundHttpException("Свалка $id не обнаружена!");

            $data['info'] = $info;
        }

        return $app['twig']->render('form.twig', $data);
    }

    $app->get('/form', function() use($app) {
        return form(false);
    });

    $app->get('/form/{idd}', function($idd) use($app) {
        debug_to_console('asda');
        debug_to_console($idd);
        debug_to_console('asda');
        debug_to_console('asda');
        return form($idd);
    });

    function debug_to_console( $data ) {
        $output = $data;
        if ( is_array( $output ) )
            $output = implode( ',', $output);
    
        echo "<script>console.log( 'Debug Objects: " . $output . "' );</script>";
    }

    function edit($edit, $id, Request $req) {
        debug_to_console('AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAa');
        global $app;
        $conn = $app['db'];
        debug_to_console($req);
        

        $nameDump = htmlspecialchars(trim($req->get('nameDump')));
        if (strlen($nameDump) == 0)
            return json_encode(['result' => 'Error', 'message' => '1)Название свалки не может оставаться пустым ']);
        echo $nameDump;
        $dateFind = htmlspecialchars(trim($req->get('dateFind')));
        if (strlen($dateFind) == 0)
            return json_encode(['result' => 'Error', 'message' => '2)Дата нахождения не может оставаться пустым']);
        $dateStatement = htmlspecialchars(trim($req->get('dateStatement')));
        if (strlen($dateStatement) == 0)
            return json_encode(['result' => 'Error', 'message' => '3)Дата подачи заявления не может оставаться пустым']);
        $photoLocation = htmlspecialchars(trim($req->get('photoLocation')));
        if (strlen($photoLocation) == 0)
            return json_encode(['result' => 'Error', 'message' => '4)Папка с фото не может оставаться пустым']);
        $location = htmlspecialchars(trim($req->get('location')));
        if (strlen($location) == 0)
            return json_encode(['result' => 'Error', 'message' => '5)Местоположение не может оставаться пустым']);

        $data = array(
            'name' => $nameDump,
            'dateFind' => $dateFind,
            'dateStatement' => $dateStatement,
            'photoLocation' => $photoLocation,
            'location' => $location
        );
        debug_to_console($data);

        if(!$edit) {
            $conn->insert('landfill', $data);
        } else {
            $result = $conn->update('landfill', $data, ['id' => $id]);
        }

        return json_encode(['result' => 'OK']);
    }

    $app->post('/landfilla', function(Request $req) use($app) {
        debug_to_console('a');
        return edit(false, -1, $req);
    });

    $app->post('/landfill/{id}', function(Request $req, $id) use($app) {
        debug_to_console('b');
        return edit(true, $id, $req);
    });


    $app->delete('/landfill/{id}', function($id) use($app) {
        $conn = $app['db'];
        $conn->delete('landfill', ['id' => $id]);
        return '';
    });

    $app->run();



            
    // $app->get('/', function () use ($app) {
    //     /**@var $conn Connection */
    //     $conn = $app['db'];
    //     $students = $conn->fetchAll('select * from students');
    //     return $app['twig']->render('students.twig', ['students' => $students]);
    // });

    // $app->get('/students/{id}', function ($id) use ($app) {
    //     /**@var $conn Connection */
    //     $conn = $app['db'];
    //     $student = $conn->fetchAssoc('select * from students where id = ?', [$id]);
    //     if (!$student) {
    //         throw new NotFoundHttpException("Нет такого студента - $id");
    //     }
    //     $subjects = $conn->fetchAll('select * from subjects');
    //     $scores = $conn->fetchAll('select * from scores where student_id = ?', [$id]);
    //     $scorez = [];
    //     foreach ($scores as $score) {
    //         $scorez[$score['subject_id']] = $score['score'];
    //     }
    //     return $app['twig']->render('student.twig', ['student' => $student, 'subjects' => $subjects, 'scorez' => $scorez]);
    // });

    // $app->post('/students', function (Request $req) use ($app) {
    //     /**@var $conn Connection */
    //     $conn = $app['db'];
    //     $name = $req->get('name');
    //     $conn->insert('students', ['name' => $name]);
    //     return $app->redirect('/');
    // });

    // $app->delete('/students/{id}', function ($id) use ($app) {
    //     /**@var $conn Connection */
    //     $conn = $app['db'];
    //     $conn->delete('students', ['id' => $id]);
    //     return $app->redirect('/');
    // });

    // $app->put('/students/{id}/scores', function (Request $req, $id) use ($app) {
    //     /**@var $conn Connection */
    //     $conn = $app['db'];
    //     $conn->transactional(function (Doctrine\DBAL\Connection $conn) use ($id, $req) {
    //         $conn->delete('scores', ['student_id' => $id]);
    //         foreach ($req->get('scores') as $subject_id => $score) {
    //             if ($score) {
    //                 $conn->insert('scores', ['student_id' => $id, 'subject_id' => $subject_id, 'score' => $score]);
    //             }
    //         }
    //     });
    //     return $app->redirect("/students/$id");
    // });

    // $app->run();