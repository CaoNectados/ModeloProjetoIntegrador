<?php

abstract class BaseRepository 
{
    protected PDO $db;
    protected string $tabela; // Cada classe filha vai preencher isso

    public function __construct(PDO $db) 
    {
        $this->db = $db;
    }

    // Método genérico para buscar por ID
    public function buscarPorId(int $id) 
    {
        // Usa a $tabela definida pela classe filha
        $stmt = $this->db->prepare("SELECT * FROM {$this->tabela} WHERE id = :id");
        $stmt->execute(['id' => $id]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Método genérico para deletar
    public function deletar(int $id): bool 
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->tabela} WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    // O método de salvar costuma ser abstrato ou usar arrays dinâmicos
    // Aqui um exemplo simples inserindo um array de dados
    public function salvar(array $dados): bool 
    {
        $colunas = implode(', ', array_keys($dados));
        $valores = ':' . implode(', :', array_keys($dados));
        
        $sql = "INSERT INTO {$this->tabela} ($colunas) VALUES ($valores)";
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute($dados);
    }
}

