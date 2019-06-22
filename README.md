# populator


## Usage

```php
<?php

namespace App\Http\Controllers;


use App\User;
use Greabock\Populator\Populator;
use Illuminate\Http\Request;

class UserController
{
    /**
     * @param $id
     * @param Request $request
     * @param Populator $populator
     * @return User|null
     */
    public function put($id, Request $request, Populator $populator): User
    {
        /** @var User $user */
        $user = $populator->populate(User::findOrNew($id), $request->input());

        return $user;
    }
}

```
