<?php
require __DIR__.'/vendor/autoload.php';
require 'respuestas.php';

use Phpml\FeatureExtraction\TokenCountVectorizer;
use Phpml\Tokenization\WordTokenizer;
use Phpml\FeatureExtraction\TfIdfTransformer;


try {
    $classifier = unserialize(file_get_contents('model.dat'));
    $vectorizer = unserialize(file_get_contents('vectorizer.dat'));
    $tfIdf = unserialize(file_get_contents('tfidf.dat'));
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error en el sistema. Por favor, inténtalo más tarde.']);
    exit;
}


$userMessage = trim(strtolower($_POST['message'] ?? ''));

if (empty($userMessage)) {
    http_response_code(400);
    echo json_encode(['error' => 'Por favor, escribe tu mensaje.']);
    exit;
}


function procesarPedidoPollo($mensaje) {

 $patrones = [
       
        '/(quiero|deseo|ordenar|pedir|solicitar|me gustaría|necesito)\s+(\d+)\s*(pollo|pollos|medio|medios|cuarto|cuartos|entero|enteros|combo|combos)?\s*(clásico|clasico|picante|bbq|especial|familiar|express|promocional|ejecutivo|oferta)?/i',
        
        '/(combo|menú|paquete|especial)\s*(familiar|ejecutivo|promocional|picante|bbq|express|oferta)/i',
                
        '/(para|de)\s*(fiesta|evento|reunión|oficina|familia|llevar)/i',
        
        '/con\s*(papas|ensalada|bebidas|salsa|extras|adicionales)/i'
    ];
    
    $datos = [
        'tipo' => 'pollo',
        'cantidad' => 1,
        'variante' => 'entero',
        'sabor' => 'clásico',
        'combo' => null,
        'extras' => [],
        'ocasion' => 'normal',
        'hora' => null
    ];
    
    foreach ($patrones as $patron) {
        if (preg_match($patron, $mensaje, $matches)) {
            
            if (isset($matches[2]) && is_numeric($matches[2])) {
                $datos['cantidad'] = (int)$matches[2];
            }
            

            if (isset($matches[3])) {
                $variantes = [
                    'medio' => 'medio pollo',
                    'medios' => 'medio pollo',
                    'cuarto' => 'cuarto de pollo',
                    'cuartos' => 'cuarto de pollo',
                    'entero' => 'pollo entero',
                    'enteros' => 'pollo entero'
                ];
                $datos['variante'] = $variantes[strtolower($matches[3])] ?? 'pollo entero';
            }
            

            if (isset($matches[4])) {
                $sabores = [
                    'clásico' => 'clásico',
                    'clasico' => 'clásico',
                    'picante' => 'picante',
                    'bbq' => 'BBQ',
                    'especial' => 'especial de la casa',
                    'familiar' => 'combo familiar',
                    'express' => 'combo express',
                    'promocional' => 'paquete promocional',
                    'ejecutivo' => 'menú ejecutivo',
                    'oferta' => 'oferta especial'
                ];
                $datos['sabor'] = $sabores[strtolower($matches[4])] ?? 'clásico';
            }
            

            if (isset($matches[1]) && in_array(strtolower($matches[1]), ['combo', 'menú', 'paquete', 'especial'])) {
                $datos['combo'] = $matches[2] ?? 'estándar';
            }
            

            if (strpos($mensaje, 'con') !== false) {
                if (strpos($mensaje, 'papas')) $datos['extras'][] = 'papas extras';
                if (strpos($mensaje, 'ensalada')) $datos['extras'][] = 'ensalada';
                if (strpos($mensaje, 'bebidas')) $datos['extras'][] = 'bebidas';
                if (strpos($mensaje, 'salsa')) $datos['extras'][] = 'salsa extra';
            }
            
         
            if (strpos($mensaje, 'fiesta') || strpos($mensaje, 'evento')) {
                $datos['ocasion'] = 'fiesta/evento';
            } elseif (strpos($mensaje, 'reunión') || strpos($mensaje, 'familia')) {
                $datos['ocasion'] = 'reunión familiar';
            } elseif (strpos($mensaje, 'oficina')) {
                $datos['ocasion'] = 'oficina';
            } elseif (strpos($mensaje, 'llevar')) {
                $datos['ocasion'] = 'para llevar';
            }
        }
    }
    

    if (preg_match('/(\d{1,2}:\d{2}|\d{1,2}\s*(?:am|pm)?)/i', $mensaje, $horaMatches)) {
        $datos['hora'] = $horaMatches[1];
    }
    
    return $datos;
}


$directResponses = [
    'qué sabores tienen' => 'sabores',
    'qué sabores ofrecen' => 'sabores',
    'quiero hacer un pedido' => 'pedido_instrucciones',
    'deseo comprar pollo' => 'pedido_pollo',
    'cómo pedir pollo' => 'pedido_instrucciones',
    'quiero un pollo' => 'pedido_pollo',
    'deseo ordenar' => 'pedido_pollo',
    'cómo hago para reservar' => 'reservas',
    'quiero un combo' => 'combos',
    'me gustaría pedir para llevar' => 'pedido_pollo',
    'cómo solicito un pedido grande' => 'pedidos_grandes',
    'quiero medios pollos' => 'pedido_pollo',
    'deseo ordenar con anticipación' => 'reservas',
    'cómo pido el pollo bbq' => 'sabores',
    'quiero hacer un pedido express' => 'pedido_express',
    'deseo comprar para una fiesta' => 'eventos',
    'cómo encargo para un evento' => 'eventos',
    'quiero el especial de la casa' => 'especiales',
    'deseo ordenar con papas extras' => 'pedido_pollo',
    'cómo pido para oficina' => 'eventos',
    'quiero un paquete promocional' => 'promociones',
    'deseo el combo picante' => 'combos',
    'cómo solicito entrega urgente' => 'pedido_express',
    'quiero pedir con ensalada' => 'pedido_pollo',
    'deseo el menú ejecutivo' => 'combos',
    'cómo hago pedido por primera vez' => 'pedido_instrucciones',
    'quiero cuartos de pollo' => 'pedido_pollo',
    'deseo ordenar con bebidas' => 'pedido_pollo',
    'cómo pido para reunión familiar' => 'eventos',
    'quiero el lunes de oferta' => 'promociones',
    'deseo combinar sabores' => 'sabores',
    'cómo hago pedido recurrente' => 'reservas',
    'quiero con extras de salsa' => 'pedido_pollo'
];


foreach ($directResponses as $key => $responseType) {
    if (strpos($userMessage, $key) !== false) {
        $intention = $responseType;
        break;
    }
}


if (!isset($intention)) {
   
    $newSample = [$userMessage];
    try {
        $vectorizer->transform($newSample);
        $tfIdf->transform($newSample);
        $rawIntention = $classifier->predict($newSample)[0];
        
        
        $intentionMap = [
            'pedidos' => 'pedido_pollo',
            'horarios' => 'horario',
            'saludos' => 'saludo',
            'despedidas' => 'despedida',
            'reservas' => 'reservas',
            'combos' => 'combos',
            'eventos' => 'eventos',
            'sabores' => 'sabores'
        ];
        
        $intention = $intentionMap[$rawIntention] ?? 'default';
    } catch (Exception $e) {
        error_log("ML Error: " . $e->getMessage());
        $intention = 'default';
    }
}


$response = '';
$extraData = [];

switch ($intention) {
    case 'sabores':
        $response = "Nuestros sabores de pollo disponibles:\n\n";
        $response .= "•  Pollo Clásico - Q55 (entero)\n";
        $response .= "•  Pollo Picante - Q60 (entero)\n";
        $response .= "•  Pollo BBQ - Q65 (entero)\n";
        $response .= "•  Especial de la Casa - Q70 (entero)\n\n";
        $response .= "También ofrecemos:\n";
        $response .= "• 1/2 Pollo (Medio) desde Q30\n";
        $response .= "• 1/4 Pollo (Cuarto) desde Q20\n\n";
        $response .= "¿Qué sabor te gustaría y en qué presentación?";
        break;
        
    case 'pedido_pollo':
        $pedido = procesarPedidoPollo($userMessage);
        
        if ($pedido['hora']) {
            $response = "Pedido registrado:\n\n";
            $response .= "• {$pedido['cantidad']} {$pedido['variante']} {$pedido['sabor']}\n";
            
            if (!empty($pedido['extras'])) {
                $response .= "• Extras: " . implode(', ', $pedido['extras']) . "\n";
            }
            
            $response .= "• Para: {$pedido['ocasion']}\n";
            $response .= "• Hora de entrega: {$pedido['hora']}\n\n";
            $response .= "¿Necesitas algo más o confirmamos el pedido?";
        } else {
            $response = "Por favor, indícanos a qué hora deseas recibir tu pedido de ";
            $response .= "{$pedido['cantidad']} {$pedido['variante']} {$pedido['sabor']}";
            
            if (!empty($pedido['extras'])) {
                $response .= " con " . implode(' y ', $pedido['extras']);
            }
            
            $response .= ". ¿Para qué horario lo quieres?";
        }
        break;
        
    case 'combos':
        $response = "Nuestros combos disponibles:\n\n";
        $response .= " Combo Familiar - Q120\n";
        $response .= "• 1 pollo entero (clásico o picante)\n";
        $response .= "• 2 porciones grandes de papas\n";
        $response .= "• 2 ensaladas\n";
        $response .= "• 2 bebidas\n\n";
        
        $response .= " Menú Ejecutivo - Q45\n";
        $response .= "• 1/2 pollo\n";
        $response .= "• 1 porción de papas\n";
        $response .= "• 1 bebida\n\n";
        
        $response .= " Combo Express - Q35\n";
        $response .= "• 1/4 pollo\n";
        $response .= "• Papas pequeñas\n";
        $response .= "• 1 bebida pequeña\n\n";
        
        $response .= "¿Qué combo te interesa o prefieres armar tu propio pedido?";
        break;
        
    case 'eventos':
        $response = "Para eventos y reuniones especiales ofrecemos:\n\n";
        $response .= " Paquete Fiesta (para 10 personas) - Q300\n";
        $response .= "• 3 pollos enteros (mezcla de sabores)\n";
        $response .= "• 5 porciones grandes de papas\n";
        $response .= "• 5 ensaladas\n";
        $response .= "• 10 bebidas\n\n";
        
        $response .= " Paquete Oficina (para 5 personas) - Q180\n";
        $response .= "• 2 pollos enteros\n";
        $response .= "• 3 porciones grandes de papas\n";
        $response .= "• 5 bebidas\n\n";
        
        $response .= "¿Para cuántas personas es el evento y qué fecha/hora necesitas?";
        break;
        
    case 'reservas':
        $response = "Para reservas y pedidos con anticipación:\n\n";
        $response .= "1. Puedes llamarnos al 1234-5678\n";
        $response .= "2. Hacer el pedido aquí indicando la fecha y hora\n";
        $response .= "3. Visitar nuestro local para coordinar detalles\n\n";
        $response .= "¿Qué día y hora necesitas tu pedido?";
        break;
        
    case 'pedido_instrucciones':
        $response = "Puedes hacer tu pedido de varias formas:\n\n";
        $response .= "1. Di directamente lo que quieres:\n";
        $response .= "   - 'Quiero 2 pollos picantes enteros para las 7pm'\n";
        $response .= "   - 'Deseo 1 combo familiar con extra de salsa'\n\n";
        $response .= "2. Responde a mis preguntas para armar tu pedido paso a paso\n\n";
        $response .= "3. Elige una opción de nuestro menú:\n";
        $response .= "   - 'Quiero ver los combos'\n";
        $response .= "   - 'Muéstrame los sabores disponibles'\n\n";
        $response .= "¿Cómo prefieres hacer tu pedido hoy?";
        break;
        
    default:
        $category = [
            'horario' => 'horario',
            'saludo' => 'saludo',
            'despedida' => 'despedida'
        ][$intention] ?? 'default';
        
        $response = $respuestas[$category][array_rand($respuestas[$category])];
}



header('Content-Type: application/json');
echo json_encode([
    'intention' => $intention,
    'response' => $response,
    'timestamp' => date('Y-m-d H:i:s')
]);
?>