<?php

/**
 * @author    Erofeev Artem <artem.erof1@gmail.com>
 * @author    Molchanov Danila <danila.molchanovv@gmail.com>
 * @copyright Copyright (c) 2022, PIK Digital
 * @see       https://pik.digital
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Pik\Bundle\ReindexerBundle\Model;

use Pik\Bundle\ReindexerBundle\Client\ClientInterface;

abstract class AbstractModel implements ModelInterface
{
    protected string $source;

    public function __construct(
        private ClientInterface $connection,
    ) {
        $this->connection->setNamespaceName($this->source)->setNamespace();
    }

    
    public function getSource(): string
    {
        return $this->source;
    }

    
    public function delete(int $id): void
    {
        $this->connection->deleteItem([
            'id' => $id,
        ]);
    }

    
    public function getConnection(): ClientInterface
    {
        return $this->connection;
    }

    
    public function updateField(int $id, string $field, mixed $value): ClientInterface
    {
        $sql = "UPDATE $this->source SET $field = $value WHERE id = $id";

        return $this->connection->get($sql);
    }


    public function reindex(array $data, bool $update = false): void
    {
        $data = $this->mapping($data);
        if (!$update) {
            $this->connection->saveItem($data);
        } else {
            $this->connection->updateItem($data);
        }
    }


    public function mapping(array $data): array
    {
        if (empty($this->_mapping)) {
            return $data;
        }

        foreach ($data as $key => $value) {
            // если не задан мэппинг, то удаляем данные с таким ключем
            if (!isset($this->_mapping[$key])) {
                unset($data[$key]);
                continue;
            }

            // приводим тип данных, если задан тип (и значение это не значение по умолчанию)
            if (!empty($this->_mapping[$key][0]) && (!array_key_exists(
                2,
                $this->_mapping[$key],
            ) || $value !== $this->_mapping[$key][2])) {
                $value = call_user_func($this->_mapping[$key][0], $value);
            }

            // преобразуем индекс, если задано преобразование
            if (isset($this->_mapping[$key][1])) {
                unset($data[$key]);
                $data[$this->_mapping[$key][1]] = $value;
            } else {
                $data[$key] = $value;
            }
        }

        return $data;
    }

    
    public function getById(int $id): mixed
    {
        return $this->connection->getById($id)->getItem();
    }

    
    public function getByIds(array $ids): mixed
    {
        $strIds = implode(',', $ids);
        $sql = "SELECT * FROM $this->source WHERE id IN ($strIds)";

        return $this->connection->get($sql)->getItems();
    }

    
    public function setIsAssociative(bool $isAssociative): self
    {
        $this->getConnection()->setIsAssociative($isAssociative);

        return $this;
    }
}
