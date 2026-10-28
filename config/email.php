<?php



$config_email = [
    // Configuração SMTP
    'smtp_host' => getenv('SMTP_HOST') ?: 'smtp.gmail.com',
    'smtp_port' => getenv('SMTP_PORT') ?: 587,
    'smtp_user' => getenv('SMTP_USER') ?: '',
    'smtp_password' => getenv('SMTP_PASSWORD') ?: '',
    'smtp_secure' => getenv('SMTP_SECURE') ?: 'tls', // 'tls' ou 'ssl'
    
    // Remetente
    'remetente_email' => getenv('REMETENTE_EMAIL') ?: '',
    'remetente_nome' => getenv('REMETENTE_NOME') ?: 'Sistema Recanto das Araras',
    
    // Modo debug (desabilitar em produção)
    'debug' => filter_var(getenv('EMAIL_DEBUG') ?: false, FILTER_VALIDATE_BOOLEAN),
];

?>
