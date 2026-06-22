<?php

function processarUploadImagem(array $arquivo, string $pasta_destino, string $prefixo = 'img_'): array
{
    if (!isset($arquivo['error']) || $arquivo['error'] !== UPLOAD_ERR_OK) {
        return ['sucesso' => false, 'caminho' => null, 'erro' => 'Nenhum arquivo válido foi enviado.'];
    }

    $tamanho_maximo = 5 * 1024 * 1024;
    if ($arquivo['size'] > $tamanho_maximo) {
        return ['sucesso' => false, 'caminho' => null, 'erro' => 'A imagem deve ter no máximo 5MB.'];
    }

    $extensao = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));
    $extensoes_permitidas = ['jpg', 'jpeg', 'png', 'webp'];
    if (!in_array($extensao, $extensoes_permitidas, true)) {
        return ['sucesso' => false, 'caminho' => null, 'erro' => 'Formato de imagem inválido! Use JPG, PNG ou WEBP.'];
    }

    $info_imagem = @getimagesize($arquivo['tmp_name']);
    if ($info_imagem === false) {
        return ['sucesso' => false, 'caminho' => null, 'erro' => 'O arquivo enviado não é uma imagem válida.'];
    }

    $mimes_permitidos = ['image/jpeg', 'image/png', 'image/webp'];
    if (!in_array($info_imagem['mime'], $mimes_permitidos, true)) {
        return ['sucesso' => false, 'caminho' => null, 'erro' => 'Tipo de imagem não suportado.'];
    }

    $novo_nome = uniqid($prefixo, true) . '.' . $extensao;
    $destino = rtrim($pasta_destino, '/') . '/' . $novo_nome;

    if (!move_uploaded_file($arquivo['tmp_name'], $destino)) {
        return ['sucesso' => false, 'caminho' => null, 'erro' => 'Erro ao salvar o arquivo no servidor.'];
    }

    @chmod($destino, 0644);

    return ['sucesso' => true, 'caminho' => $novo_nome, 'erro' => null];
}