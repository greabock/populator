# Populator
**Proof of concept смешанной гидрации данных для Eloquent**

> На данный момент работает только с uuid

## Идея

Работая с данными по API, мы часто получаем эти сущности с вложенными отношениями, но вот при отправке данных, вложенные отношения приходится обрабатывать вручную. Данный гидратор позволяет не думать об этом. И это сильно ускоряет разработку.

## Использование

Использовать эту штуку очень просто

Бэкэнд:
```php
<?php

namespace App\Http\Controllers;

use App\Post;
use Exception;
use Greabock\Populator\Populator;
use Illuminate\Http\Request;

class UserController
{
    /**
     * @param $id
     * @param Request $request
     * @param Populator $populator
     * @return Post
     * @throws Exception
     */
    public function put(Request $request, Populator $populator): Post
    {
        $post = Post::findOrNew($request->get('id'));
        $populator->populate($post, $request->input());

        // здесь мы можем сделать что-то до того, как изменения отправятся в базу.
        
        $populator->flush();
        
        return $post;
    }
}
```

Пример js (не делайте так - это просто пример):
```js
import uuid from 'uuid/v4'

class Post {
  public constructor(data) {
    if(!data.id) {
        data.id = uuid()
    }
    Object.assign(this, data)
  }
  
  addTag (tag) {
    this.tags.push(tag)
  }
  
  addImage (image) {
    this.images.push(image)
  }
}

class Tag {
  constructor(data) {
    if(!data.id) {
        data.id = uuid()
    }
    Object.assign(this, data)
  }
}

let post, tags;

//
function loadTags () {
  fetch('tags')
    .then(response => response.json())
    .then(tagsData => tags = data.map(tagdata => new Tag(tagdata)))

}

function loadPost (id) {
  fetch(`posts/${id}`)
    .then(response => response.json())
    .then(data => post = new Post(data))
}


function addTag(tag) {
    post.addTag(tag)
}

function savePost(post) {
  fetch(`posts/${post.id}`, {method: 'PUT', body: JSON.stringify(post)})
    .then(response => response.json())
    .then(data => alert(`Post ${data.title} saved!`))
}

loadTags()
loadPost(1)

// После того, как всё загружено:

post.addTag(tags[0])
post.title = 'Hello World!'

savePost(post)

```

## Особенности заполнения

### Плоские сущности
**Возьмем простой пример:**
```json
{
  "name": "Greabock",
  "email": "greabock@gmail.com",
}
```

Так как в переданных данных отсутствует поле `id` (или другое поле, которе было укзано в `$primaryKey` модели), гидратор создаст новую сущность. И наполнит ее передаными данными используя стандартный метод `fill`.
В этом случае для модели будет сразу же сгенерирован `id`. 

**Пример с идентификатором:**
```json
{
  "id" : "123e4567-e89b-12d3-a456-426655440000",
  "name": "Greabock",
  "email": "greabock@gmail.com",
}
```

В этом примере `id` был передан - поэтому гидратор попытается найти такую сущность в базе данных. Однако, если у него не получится найти такую запись в базе данных, то он создаст новую сущность с переданным `id` .
В любом случае, гидратор заполнит эту модель переданными `email` и `name`. В этом случае, поведение похоже на `User::findORNew($id)`.

### HasOne

```json
{
  "id": "123e4567-e89b-12d3-a456-426655440000",
  "name": "Greabock",
  "email": "greabock@gmail.com",
  "account": {
    "id": "2474cbbf-8e29-492e-9a66-d6335b9b3188",
    "active": true,
  }
}
```
В данном случае, гидратор поступит с сущностью перового уровня (пользователем) так же, как в примере с идентификатором. Затем, он попытается найти и аккаунт - если не найдет (а в текущем примере нет `id`), то создаст новый. Если найдет но с другим идентификатором, то заместит его вновь созданным. Старый же аккаунт будет удалён. Само собой в всязанное поле поста (например `user_id` или `author_id` - в зависимости от того, как это указано в отношении `User::posts()`), будет записан идентификатор пользователя.

### HasMany
```json
{
  "id": "123e4567-e89b-12d3-a456-426655440000",
  "name": "Greabock",
  "email": "greabock@gmail.com",
  "posts": [
    {
      "id": "1286d5bb-c566-4f3e-abe0-4a5d56095f01",
      "title": "foo",
      "text": "bar"
    },
    {
      "id": "d91c9e65-3ce3-4bea-a478-ee33c24a4628",
      "title": "baz",
      "text": "quux"
    },
    {
      "title": "baz",
      "text": "quux"
    }
  ]
}
```

В примере с отношением, "многие к одному", гидратор поступит с каждой записью поста, как в примерере `HasOne`. Кроме того, все записи, которые не были представлены в переданном массиве постов - будут удалены.


### BelongsTo

```json
{
  "id" : "123e4567-e89b-12d3-a456-426655440000",
  "name": "Greabock",
  "email": "greabock@gmail.com",
  "organization": {
    "id": "1286d5bb-c566-4f3e-abe0-4a5d56095f01",
    "name": "Acme"
  },
}
```
Хотя этот пример и выглядит как `HasOne`, работает он иначе. Если такая организация будет найдена гидратором в базе данных, то пользователь будет к ней привязан через поле отношения. С другой стороны, если такой записи не будет, то пользователь получит в это поле `null`. Все прочие поля связанной записи (организации) будут проигнорированы - так как `User` не является `aggregate root` по отношению к `Organization`, следовательно, нельзя управлять полями организации через объект пользователя.


### BelongsToMany

```json
{
  "id" : "123e4567-e89b-12d3-a456-426655440000",
  "name": "Greabock",
  "email": "greabock@gmail.com",
  "roles": [
    {
      "id": "dcb41b0c-8bc1-490c-b714-71a935be5e2c",
      "pivot": { "sort": 0 }
    }
  ]
}
```

Этот пример похож на смесь из `HasMany` (в том смысле, что все непредставленные записи будут удалены из пивота) и `BlongsTo` (все поля, кроме поля `$primaryKey` будут проигнорированы, по причинам изложеным выше в разделе `belongsTo`). Обратите внимание, что работа с пивотом так же доступна.

В планах добавить поддержку и полиморфных отношений.

---
> Всё описанное работает рекурсивно, и справедливо для любой степени вложенности.

---

## Особенности вывода

Стоит также отметить, что все переданные отношения будут добавлены в сущности при выводе:

```php
    $user = $populator->populate(User::class, [
        'id'    => '123e4567-e89b-12d3-a456-426655440000',
        'name'  => 'Greabock',
        'email' => 'greabock@gmail.com',
        'roles' => [
            [
              'id'    => 'dcb41b0c-8bc1-490c-b714-71a935be5e2c',
              'pivot' => ['sort' => 0],
            ],
        ],
    ]);
    
    $user->relationLoaded('roles'); // true
    // хотя flush еще не сделан, все отношения уже прописаны, и нет необходимости загружать их дополнтительно.
    // например $user->roles - не вызовет повтороно запроса к бд.

    $populator->flush();
    // Только после этого сущность со всеми ее связями попадёт в базу данных. 
```


## TODO
- добавить возможность персиста сущности не прошедшей через гидратор 








