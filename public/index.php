<?php

require_once __DIR__ . '/../app/core/Autoload.php';
require_once __DIR__ . '/../app/config/Config.php';

use app\core\Router;

$router = new Router();

/*

    TODO: Cadastrar uma nova entidade;
    TODO: Editar uma entidade existente;
    TODO: Excluir uma entidade;
    TODO: Listar todas as entidades cadastradas;
    TODO: Exibir os dados de uma única entidade, com base em seu identificador.

    TODO: Implemente validações nos campos da entidade (ex: campos obrigatórios, 
    formatos, limites de tamanho, etc.).

    TODO: Implemente pelo menos uma regra de negócio

    TODO: Controle de Acesso

$router->get('/', 'JogadorController@listarTodos');

// Jogador Routes
$router->get('/jogadores', 'JogadorController@listarTodos');
$router->get('/jogadores/jogador', 'JogadorController@verJogador');
$router->get('/jogadores/cadastrar', 'JogadorController@criar');

$router->post('/jogadores/salvar', 'JogadorController@salvar');
$router->get('/jogadores/editar', 'JogadorController@editar');
$router->post('/jogadores/atualizar', 'JogadorController@atualizar');
$router->get('/jogadores/excluir', 'JogadorController@excluir');
*/


$router->run();