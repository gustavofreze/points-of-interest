# Points Of Interest (POIs)

[![License](https://img.shields.io/badge/license-MIT-green)](LICENSE)

* [Overview](#overview)
* [Endpoints](#endpoints)
* [Instalação](#installation)
    - [Repositório](#repository)
    - [Configuração](#configure)
* [FAQ](#faq)

<div id="overview"></div> 

## Overview

Implementação do desafio
[Pontos de Interesse por GPS](https://github.com/backend-br/desafios/blob/master/points-of-interest/PROBLEM.md),
do repositório backend-br.

### Pontos de Interesse por GPS

Seu desafio será implementar um serviço para a empresa XY Inc., especializada na produção de excelentes receptores
GPS (Global Positioning System).
A diretoria está empenhada em lançar um dispositivo inovador que promete auxiliar pessoas na localização de pontos de
interesse (POIs), e precisa muito de sua ajuda.
Você foi contratado para desenvolver a plataforma que fornecerá toda a inteligência ao dispositivo. Esta plataforma deve
ser baseada em serviços REST, para flexibilizar a integração.

#### Exemplo

Considere a seguinte base de dados de POIs:

- 'Lanchonete' (x=27, y=12)
- 'Posto' (x=31, y=18)
- 'Joalheria' (x=15, y=12)
- 'Floricultura' (x=19, y=21)
- 'Pub' (x=12, y=8)
- 'Supermercado' (x=23, y=6)
- 'Churrascaria' (x=28, y=2)

Dado o ponto de referência (x=20, y=10) indicado pelo receptor GPS, e uma distância máxima de 10 metros, o serviço deve
retornar os seguintes POIs:

- Lanchonete
- Joalheria
- Pub
- Supermercado

#### Regras

- Cadastrar pontos de interesse, com 03 atributos: nome do POI, coordenada X (inteiro não negativo)
  e coordenada Y (inteiro não negativo).
- Os POIs devem ser armazenados em uma base de dados.
- Listar todos os POIs cadastrados.
- Listar os POIs por proximidade. Este serviço receberá uma coordenada X e uma coordenada Y, especificando um ponto de
  referência, bem como uma distância máxima (d-max) em metros. O serviço deverá retornar todos os POIs da base de dados
  que estejam a uma distância menor ou igual a d-max a partir do ponto de referência.

<div id='endpoints'></div> 

## Endpoints

URLs de acesso:

| Ambiente | DNS                                 | 
|:---------|:------------------------------------|
| `Local`  | http://points-of-interest.localhost |

### Cadastrar POI

Cadastrar um ponto de interesse.

**[POST]** `{{host}}/pois`

**Request**

| Parâmetro            |  Tipo  | Descrição                           | Obrigatório |
|:---------------------|:------:|:------------------------------------|:-----------:|
| `name`               | String | Nome do ponto de interesse.         |     Sim     |    
| `point.x_coordinate` |  int   | Coordenada X do ponto de interesse. |     Sim     |             
| `point.y_coordinate` |  int   | Coordenada Y do ponto de interesse. |     Sim     |

```json
{
    "name": "Pub",
    "point": {
        "x_coordinate": 12,
        "y_coordinate": 8
    }
}
```

**Response**

```
HTTP/1.1 201 Created
Content-Type: application/json
```

```json
{
    "name": "Pub",
    "point": {
        "x_coordinate": 12,
        "y_coordinate": 8
    }
}
```

### Listar POIs

Listar todos os pontos de interesse cadastrados, ou, utilizando os filtros, apenas os pontos de interesse cadastrados,
que estejam a uma distância menor ou igual a `distance` a partir do ponto de referência (`x_coordinate`
e `y_coordinate`).

**[GET]** `{{host}}/pois?x_coordinate=20&y_coordinate=10&distance=10`

**Request**

| Parâmetro      | Tipo | Descrição                           | Obrigatório |
|:---------------|:----:|:------------------------------------|:-----------:|
| `distance`     | int  | Distância máxima em metros.         |     Não     |             
| `x_coordinate` | int  | Coordenada X do ponto de interesse. |     Não     |             
| `y_coordinate` | int  | Coordenada Y do ponto de interesse. |     Não     |

**Response**

```
HTTP/1.1 200 OK
Content-Type: application/json
```

```json
[
    {
        "name": "Pub",
        "point": {
            "x_coordinate": 12,
            "y_coordinate": 8
        }
    }
]
```

<div id='installation'></div> 

## Instalação

<div id='repository'></div> 

### Repositório

Para clonar o repositório usando a linha de comando, execute:

```bash
git clone https://github.com/gustavofreze/points-of-interest.git
```

<div id='configure'></div> 

### Configuração

Para instalar dependências do projeto localmente, execute:

```bash
make configure
```

Para iniciar os contêineres do projeto, execute:

```bash
make start
```

> Você pode verificar outros comandos disponíveis executando `make help`.

<div id='faq'></div> 

## FAQ

- **Existe algum trade-off em modelar a lógica de obter os pontos de interesse, com base no ponto de referência, nos
  modelos?**

  Sim, para uma aplicação de produção, em que o banco de dados pode crescer exponencialmente. Em uma situação em que
  você tem um número enorme de pontos para processar, uma limitação de memória seria inerente. Então, faria sentido,
  essa lógica estar no banco de dados.
