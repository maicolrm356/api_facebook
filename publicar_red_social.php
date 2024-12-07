<?php

require_once __DIR__ . '/../vendor/autoload.php';

include("conexion.php");
// include("Facebook/autoload.php");
use Facebook\Facebook;
use Facebook\Exceptions\FacebookResponseException;
use Facebook\Exceptions\FacebookSDKException;



if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fecha = $_POST['fecha_resultados'];
    $redSocial = $_POST['red_social'];
} else {
    echo "Método no permitido.";
}

$query = "SELECT id_red, token_acceso, id_page, ruta_imagen, message, fecha_expiracion_token FROM adm_redes_sociales where id_red = $1";
$resultado = pg_query_params($conn, $query, [$redSocial]);

$fecha_actual = (new DateTime())->format('Y-m-d');

if ($resultado){
    try {
        while ($fila = pg_fetch_assoc($resultado)){
            $id_red = $fila['id_red'];
            $token_acces = $fila['token_acceso'];
            $id_page =  $fila['id_page'];
            $ruta_imagen = $fila['ruta_imagen'];
            $message = $fila['message'];
            $fecha_expiracion_token = (new DateTime($fila['fecha_expiracion_token']))->format('Y-m-d');
        }
    
        switch ($id_red) {
            case 1:
                // faceboook
                $url = "https://graph.facebook.com/v12.0/{$id_page}/photos";
                $app_id = "8655540201166918";
                $app_secret = "d756444ff63b389a8ed35be2e07dfffa";
                $token_acces;
                $token_acces = "EAB7AKv8HmEYBOzhX19pn8LYBZASKKxHHUw04hlIThy9BaTSKcsRj9voD9ln6dlKiyOEe95y25VfZAuNcR5lRZBrdxP90ZCdvaZCfKaafmKh78pI60HC81MY3H0qGU9K6N3YNgf3WcbtppSVstoFUN9syzSwCp644u5BCrYz6ZB4PYUYZCS9W59PsqAJEN0dcwCcFi3NRZCgfi4qI07uuJzYQbw8ZD";
                echo "red social = Facebook";
                break;
            case 2:
                // instagram
                $url = "https://graph.facebook.com/v17.0/{$id_page}/media";
                $app_id = "1080172413374093";
                $app_secret = "f8bd0afe2c8bae5e235344802fdee71c";
                $token_acces = "EAAPWaTzZBzo0BOxpIYZAQqkE0nTnIjNQjoYvVr4HRsaBaRquQZA1HxZBSDWZCyYJsXKJI4VHkcVY2Rr8ZBbGqMSuJ4aogC61bv7y0mTvXrCbxclQOjpSIAIl5ZB4yLTM1KkKRNu3OfkPbzXdoR02Q23tF5JdZC5EgCARRptUCZBNJZA9ZAICpRDg8hw1wV6IJAPJvqvgUztKA2zuNJMO6lj";
                break;
        }
    
        //Generar token si ya expiro
        if ($fecha_actual >= $fecha_expiracion_token) {
            $url = "https://graph.facebook.com/v16.0/oauth/access_token?" .
            "grant_type=fb_exchange_token&" .
            "client_id=" . urlencode($app_id) . "&" .
            "client_secret=" . urlencode($app_secret) . "&" .
            "fb_exchange_token=" . urlencode($token_acces);
    
            $response = file_get_contents($url);
            $data = json_decode($response, true);
    
            if (isset($data['access_token'])) {
                $token_acces = $data['access_token']; //nuevo token
                $type_token = $data['token_type'];
                $tiempo_expiracion = $data['expires_in'];
    
                $fecha_expiracion = new DateTime();
                $fecha_expiracion->add(new DateInterval('PT'.$tiempo_expiracion.'S'));
                $fecha_expiracion = $fecha_expiracion->format('Y-m-d');
    
                echo "Token actualizado correctamente.";
                echo "nuevo token" . $token_acces;
                echo "type token" . $type_token;
                echo "fecha expiracion: " . $fecha_expiracion;
                
                $sql_insert_new_token = "UPDATE adm_redes_sociales SET token_acceso = $1, fecha_expiracion_token = $2 WHERE id = $3";
                pg_query_params($conn, $sql_insert_new_token, [$token_acces, $fecha_expiracion, $id_red]);
                
                echo "Nuevo token en la base de datos correectamente";
    
            } else {
                echo "Error al generar un nuevo token.";
            }
        } else {
            echo "El token no ha caducado";
        }
    
    $message = mb_convert_encoding($message, 'UTF-8', 'Windows-1252');
    $message = "Publicacion de la fecha: {$fecha} \n" . $message;
    } catch (Exception $e) {
        echo " Error: " . $e;
    }
}


//consuerte
$token_acces = "EAAKhWRzMwXoBOykqfe7G56OQthFvgfZAqMI8k0a30IaD81qwoCC2imdi08tD4id2dZBMbI71qdRZB6VNHsbnRw9kFwZAejCX91jSorPl75Yj5ZAKQ0aJxsGWsGAgfnTrfjF2nlBFPZCAFhZCZB8YLBsFV0UWo4gbWIJLl2o0KcPnXZCtXus1zxrrOqPjuZCPBJe65X8faNvfiX001Uuz2WqTWpKIqWAPJeMmlzD3didW4ZD";
$id_page;
$app_id = "740354060763514";
$app_secret = "fe6b6ff40eb43d88ee1911b3f07a4f4e";

$fb = new Facebook([
    'app_id' => $app_id,
    'app_secret' => $app_secret,
    'default_graph_version' => 'v21.0',
    // 'published' => 'false',
]);

$data = [
    // 'message' => $message,
    'source' => $fb->fileToUpload('C:\Users\auxsenadesarrollo\Desktop\api facebook\imagenes_a_publicar\21-11-2024(64).png'),
];


try {
    $response = $fb->post('/me/photos', $data, $token_acces);

    echo 'imagen subida con exito: ' . $response->getGraphNode()['id'];
} catch (FacebookResponseException $e) {
    echo 'Error en la respuesta de Facebook: ' . $e->getMessage();
    exit;
} catch (FacebookSDKException $e) {
    echo 'Error en el SDK de Facebook: ' . $e->getMessage();
    exit;
}

$post = [
    'message' => $message,
];

try {
    $response = $fb->post('/me/feed', $post, $token_acces);

    echo 'Publicado con �xito: ' . $response->getGraphNode()['id'];
} catch (FacebookResponseException $e) {
    echo 'Error en la respuesta de Facebook: ' . $e->getMessage();
    exit;
} catch (FacebookSDKException $e) {
    echo 'Error en el SDK de Facebook: ' . $e->getMessage();
    exit;
}





// $fb = new Facebook([
//     'app_id' => $app_id,
//     'app_secret' => $app_secret,
//     'default_graph_version' => 'v21.0',
//     // 'published' => 'false',
// ]);

// $data = [
//     'message' => $message,
//     'url' => $ruta_imagen,
// ];


// try {
//     $response = $fb->post('/me/photos', $data, $token_acces);

//     echo 'publicacion subida con exito: ' . $response->getGraphNode()['id'];
// } catch (FacebookResponseException $e) {
//     echo 'Error en la respuesta de Facebook: ' . $e->getMessage();
//     exit;
// } catch (FacebookSDKException $e) {
//     echo 'Error en el SDK de Facebook: ' . $e->getMessage();
//     exit;
// }






//     $published = false;
//     $scheduledPublishTime = strtotime('+1 day');
    
//     // $ruta_imagen = "C:\Users\auxsenadesarrollo\Desktop\api facebook\imagenes_a_publicar\21-11-2024(64).png";

//     $params_image = [
//         "access_token" => $token_acces,
//         "url" => $ruta_imagen,
//         'published' => 'false'
//     ];

//     echo $ruta_imagen;
//     // $imageBase64 = base64_encode(file_get_contents($ruta_imagen));
//     // $params_image = [
//     //     "access_token" => $token_acces,
//     //     'source' => $imageBase64,
//     //     'published' => 'false'
//     // ];

//     $options = [
//         "http" => [
//             "header" => "Content-Type: application/json",
//             "method" => "POST",
//             "content" => http_build_query($params_image),
//             'ignore_errors' => true // Captura errores del servidor
//         ]
//     ];

//     $context = stream_context_create($options);

//     try {
//         $response = file_get_contents($url, false, $context);
//         if ($response === false){
//             echo "Error al realizar la solicitd a la api";
//         }
        
//         if (json_last_error() !== JSON_ERROR_NONE) {
//             echo "Error al decodificar JSON: " . json_last_error_msg();
//             var_dump($response);
//             exit;
//         }
//         $data = json_decode($response, true);
//         if ($data && isset($data['id'])){
//             echo "foto subida correctamente";
//             $foto_id = $data['id'];
//             $params_post =[
//                 "access_token" => $token_acces,
//                 "message" => $message,
//                 // 'scheduled_publish_time' => $scheduledPublishTime,
//                 // "attached_media" => json_encode([["media_fbid" => $foto_id]]),
//                 // "url" => $ruta_imagen,
//                 "attached_media" => json_encode([["media_fbid" => $foto_id]]),
//                 "published" => "true" // Publicar inmediatamente
//             ];
            
//             $url = "https://graph.facebook.com/v12.0/{$id_page}/feed";

//             $options_post = [
//                 "http" => [
//                     "header" => "Content-Type: application/json",
//                     "method" => "POST",
//                     "content" => http_build_query($params_post),
//                     'ignore_errors' => true
//                 ]
//             ];
            
            
//             $context_post = stream_context_create($options_post);
//             $response_post = file_get_contents($url, false, $context_post);
//             $data_post = json_decode($response_post, true);
            
//             if ($data_post && isset($data_post['id'])){
//                 $post_id = $data_post['id'];
//                 $url_publicacion = "https://www.facebook.com/{$id_page}/posts/{$post_id}";

//                 $sql_insert = "INSERT INTO control_redes_sociales(id_red, id_page, id_publicacion, ruta_imagen, fecha_publicacion, fecha_sys, url_publicacion) VALUES ($1, $2, $3, $4, CURRENT_DATE, NOW(), $5)";

//                 $resultado = pg_query_params($conn, $sql_insert, [$redSocial, $id_page, $post_id, $ruta_imagen, $url_publicacion]);

//                 $respuesta = json_encode(([
//                     "status" => "success",
//                     "message" => mb_convert_encoding("Publicaci�n realizada con exito.","UTF-8", "Windows-1252"),
//                     "url" => $url_publicacion
//                 ]));
                
//                 echo $post_id;
//                 echo "Publicacion realizada corrrectamente";
//             } else {
//                 "error al realizar la publicacion";
//                 var_dump($data_post);
//             }
//         } else {
//             echo "error al subir la imagen";
//             var_dump($data);
//         }
//     } catch (Exception $e) {
//         // echo $e;
//         echo json_encode([
//             "status" => "error",
//             "message" => "Hubo un error: " . $e->getMessage() 
//         ]);
//     }
//     } catch (Exception $e) {
//         echo " Error: " . $e;
//     }
// } else {
//     echo "Error al consultar los datos";
// }





















// if ($response) {
//     echo "el mensaje es: " . $response;
// } else {
//     die("Error al realizar la solicitud a la api " . $response);
// }
// $ch = curl_init();
// $data = [
//     'source' => new CURLFile($ruta_imagen),
//     'access_token' => $token_acces,
//     'message' => $message_encoding
// ];
// curl_setopt($ch, CURLOPT_URL, $url);
// curl_setopt($ch, CURLOPT_POST, true);
// curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
// curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
// curl_setopt($ch, CURLOPT_VERBOSE, true);

// $response = curl_exec($ch);
// $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

// if ($http_code == 200) {
//     echo "Publicación realizada con éxito\n";
//     echo $response;
// } else {
//     echo "Error al realizar la publicación: {$http_code}\n";
//     if (curl_errno($ch)) {
//         echo "cURL Error: " . curl_error($ch) . "\n";
//     }
//     echo "Respuesta: " . $response . "\n";
// }

// curl_close($ch);
?>
