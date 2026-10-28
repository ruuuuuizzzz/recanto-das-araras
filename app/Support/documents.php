<?php

/**
 * HELPER: Validação e Formatação de Documentos (CPF e CNPJ)
 */

/**
 * Formata documento (CPF ou CNPJ)
 * CPF:  12345678901 → 123.456.789-01
 * CNPJ: 12345678901234 → 12.345.678/0001-34
 */
function formatar_documento($doc) {
    // Remove tudo que não é número
    $doc_limpo = preg_replace('/\D/', '', $doc);
    
    // Se já está formatado ou vazio, retorna
    if (empty($doc_limpo)) {
        return $doc;
    }
    
    // CPF (11 dígitos)
    if (strlen($doc_limpo) === 11) {
        return substr($doc_limpo, 0, 3) . '.' . 
               substr($doc_limpo, 3, 3) . '.' . 
               substr($doc_limpo, 6, 3) . '-' . 
               substr($doc_limpo, 9, 2);
    }
    
    // CNPJ (14 dígitos)
    if (strlen($doc_limpo) === 14) {
        return substr($doc_limpo, 0, 2) . '.' . 
               substr($doc_limpo, 2, 3) . '.' . 
               substr($doc_limpo, 5, 3) . '/' . 
               substr($doc_limpo, 8, 4) . '-' . 
               substr($doc_limpo, 12, 2);
    }
    
    // Se não for CPF nem CNPJ, retorna como está
    return $doc;
}

/**
 * Remove formatação do documento (deixa apenas números)
 * 123.456.789-01 → 12345678901
 * 12.345.678/0001-34 → 12345678901234
 */
function limpar_documento($doc) {
    return preg_replace('/\D/', '', $doc);
}

/**
 * Valida CPF
 * Retorna true se válido, false se inválido
 */
function validar_cpf($cpf) {
    // Remove caracteres especiais
    $cpf = preg_replace('/\D/', '', $cpf);
    
    // Verifica se tem 11 dígitos
    if (strlen($cpf) !== 11) {
        return false;
    }
    
    // Verifica se não é sequência repetida (ex: 111.111.111-11)
    if (preg_match('/^(\d)\1{10}$/', $cpf)) {
        return false;
    }
    
    // Calcula primeiro dígito verificador
    $soma = 0;
    for ($i = 0; $i < 9; $i++) {
        $soma += intval($cpf[$i]) * (10 - $i);
    }
    $resto = $soma % 11;
    $digito1 = $resto < 2 ? 0 : 11 - $resto;
    
    // Verifica primeiro dígito
    if (intval($cpf[9]) !== $digito1) {
        return false;
    }
    
    // Calcula segundo dígito verificador
    $soma = 0;
    for ($i = 0; $i < 10; $i++) {
        $soma += intval($cpf[$i]) * (11 - $i);
    }
    $resto = $soma % 11;
    $digito2 = $resto < 2 ? 0 : 11 - $resto;
    
    // Verifica segundo dígito
    if (intval($cpf[10]) !== $digito2) {
        return false;
    }
    
    return true;
}

/**
 * Valida CNPJ
 * Retorna true se válido, false se inválido
 */
function validar_cnpj($cnpj) {
    // Remove caracteres especiais
    $cnpj = preg_replace('/\D/', '', $cnpj);
    
    // Verifica se tem 14 dígitos
    if (strlen($cnpj) !== 14) {
        return false;
    }
    
    // Verifica se não é sequência repetida
    if (preg_match('/^(\d)\1{13}$/', $cnpj)) {
        return false;
    }
    
    // Calcula primeiro dígito verificador
    $multiplicador = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
    $soma = 0;
    for ($i = 0; $i < 12; $i++) {
        $soma += intval($cnpj[$i]) * $multiplicador[$i];
    }
    $resto = $soma % 11;
    $digito1 = $resto < 2 ? 0 : 11 - $resto;
    
    // Verifica primeiro dígito
    if (intval($cnpj[12]) !== $digito1) {
        return false;
    }
    
    // Calcula segundo dígito verificador
    $multiplicador = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
    $soma = 0;
    for ($i = 0; $i < 13; $i++) {
        $soma += intval($cnpj[$i]) * $multiplicador[$i];
    }
    $resto = $soma % 11;
    $digito2 = $resto < 2 ? 0 : 11 - $resto;
    
    // Verifica segundo dígito
    if (intval($cnpj[13]) !== $digito2) {
        return false;
    }
    
    return true;
}

/**
 * Valida qualquer documento (CPF ou CNPJ)
 */
function validar_documento($doc) {
    $doc_limpo = preg_replace('/\D/', '', $doc);
    
    if (strlen($doc_limpo) === 11) {
        return validar_cpf($doc_limpo);
    }
    
    if (strlen($doc_limpo) === 14) {
        return validar_cnpj($doc_limpo);
    }
    
    return false;
}

?>