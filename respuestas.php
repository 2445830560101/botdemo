<?php
$respuestas = [
    
    'saludo' => [
        "¡Hola!  Bienvenido a Fritos, tu lugar favorito del pollo artesanal. ¿En qué puedo ayudarte hoy?",
        "¡Hola qué tal!  Somos Fritos, expertos en pollo fresco. ¿Quieres hacer un pedido o conocer nuestros sabores?",
        "¡Buen día! Aquí Fritos listos para servirte el mejor pollo. ¿Cómo podemos hacer tu día más delicioso?"
    ],    
    
    'pedido' => [
        "¡Excelente elección!  Nuestros sabores disponibles:\n\n".
        "•  Clásico - Q20\n".
        "•  Picante - Q25\n".        
        "Responde con el sabor y horario deseado (ej: *'Quiero 2 clásicos para las 11:30'*)",
        
        "¡pollo recién hecho!  Estos son nuestros sabores:\n\n".
        "1 Clásico (tradicional)\n".
        "2 Picante (toque de jalapeño)\n".       
        "Indica cuál deseas y tu horario preferido de entrega."
    ],    
    
    'horario' => [
        " *Horario de entregas:*\n".
        "Lunes a Sábado\n".
        "• Mañana: 8:30 AM - 12:00 PM\n".
        "• Tarde: 2:00 PM - 5:30 PM\n\n".
        "¿Qué franja horaria prefieres para tu pedido?",
        
        " Entregamos en estos horarios:\n\n".
        "*En Oficinas:* 9:00 AM - 3:00 PM\n".
        "*Días festivos:* 10:00 AM - 6:00 PM\n\n".
        "¿Necesitas entrega a domicilio? Próximamente tendremos esta opción "
    ],    
    
    'despedida' => [
        "¡Gracias por preferirnos! Tu pollo estará listo con todo el sabor artesanal de Fritos. ¡Hasta pronto!",
        "Pedido confirmado. ¡Te esperamos con tu pollo fresco! Que tengas un día espectacular",
        "Fue un placer atenderte. Si necesitas algo más, ¡aquí estaremos! ¡Buen provecho!"
    ],    
    
    'confirmacion' => [
        " *Pedido registrado:* %s para entrega a las %s. ¡Gracias! ¿Algo más que necesites?",
        "*Reserva confirmada:* %s listo para las %s. ¡Lo prepararemos con ingredientes frescos!"
    ],    
    
    'default' => [
        "Disculpa, no entendí bien. ¿Podrías repetirlo? Por ejemplo, escribe:\n".
        "• *'Quiero pedir pollo'*\n".
        "• *'¿Cuál es su horario?'*\n".
        "• *'Qué sabores tienen'*",
        
        "¡Ups! Creo que no capté tu mensaje. Intenta con alguna de estas opciones:\n".
        "• *'Ver menú'*\n".
        "• *'Hacer pedido'*\n".
        "• *'Horarios de entrega'*"
    ],    
    
    'extras' => [
        'ingredientes' => "Nuestro pollo lleva:  especias. ¡100% natural! ",
        'ubicacion' => "Preparamos todo . Por ahora solo entregamos en eventos, ¡pronto tendremos tienda física!",
        'pagos' => "Aceptamos: efectivo, transferencias . ¡Próximamente tarjetas!"
    ]
];


function generarRespuesta($tipo, $datos = null) {
    global $respuestas;
    
    if ($tipo == 'confirmacion' && $datos) {
        return sprintf($respuestas[$tipo][array_rand($respuestas[$tipo])], $datos['producto'], $datos['hora']);
    }
    
    if (isset($respuestas[$tipo])) {
        return $respuestas[$tipo][array_rand($respuestas[$tipo])];
    }
    
    return $respuestas['default'][array_rand($respuestas['default'])];
}
?>