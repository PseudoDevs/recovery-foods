<?php

namespace App\Http\Controllers;

use App\Models\tbl_branches;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    //Middleware
    public function __construct()
    {
        $this->middleware('auth');
    }

    //For retrieving the users
    public function getUsers()
    {
        return User::get();
    }

    //For retrieving users info
    public function get(Request $t)
    {
        $table = User::where("email", "!=", null);

        if ($t->search) { // If has value
            $table = $table->with('roles')->where("name", "like", "%" . $t->search . "%");
        }

        $return = [];
        foreach ($table->get() as $key => $value) {
            $temp = [];
            $temp['row'] = $key + 1;
            $temp['id'] = $value->id;
            $temp['branch'] = $value->branch_details;
            $temp['email'] = $value->email;
            $temp['first_name'] = $value->first_name;
            $temp['last_name'] = $value->last_name;
            $temp['name'] = $value->name;
            $temp['permissionslist'] = $value->permissionslist;
            $temp['phone_number'] = $value->phone_number;
            $temp['photo_url'] = $value->photo_url;
            $temp['roles'] = $value->roles;
            array_push($return, $temp);
        }
        $items = Collection::make($return);
        return new LengthAwarePaginator(collect($items)->forPage($t->page, $t->itemsPerPage)->values(), $items->count(), $t->itemsPerPage, $t->page, []);

    }

    //For saving users info
    public function save(Request $data)
    {
        $table = User::where("email", "!=", null);
        $table_clone = clone $table;
        if ($table_clone->where("id", $data->id)->count() > 0) {
            //Update
            $table_clone = clone $table;
            $table_clone->where("id", $data->id)->update(
                ["name" => $data->first_name . ' ' . $data->last_name,
                    "first_name" => $data->first_name,
                    "last_name" => $data->last_name,
                    "email" => $data->email,
                    "phone_number" => $data->phone_number,
                    "branch" => $data->branch,
                ]
            );

        } else {
            $return = User::create(["name" => $data->first_name . ' ' . $data->last_name,
                "first_name" => $data->first_name,
                "last_name" => $data->last_name,
                "email" => $data->email,
                "phone_number" => $data->phone_number,
                "branch" => $data->branch,
                "password" => bcrypt($data->password),
            ] + ['name' => '']);

        }
        return 0;
    }

    //For changing password
    public function change_password(Request $request)
    {
        User::where("id", auth()->user()->id)->update([
            'password' => bcrypt($request->password),
        ]);
        return true;
    }

    //For retrieving branch names
    public function branchName()
    {
        return tbl_branches::select(["branch_name", "id"])->where("status", 1)->get();
    }

    //For creating seeds
    public function createSeeder(Request $request)
    {
        $temp_db = DB::table($request->id)->get();
        $temp_str = '';

        foreach ($temp_db as $key1 => $value1) {
            $temp_str .= '[';
            $temp_num = 0;
            $temp_count = 0;
            foreach ($temp_db[$key1] as $key => $value) {
                $temp_count++;
            }

            foreach ($temp_db[$key1] as $key => $value) {
                if ($key == 'created_at' || $key == 'updated_at' || $key == 'email_verified_at') {

                } else {
                    $temp_num++;
                    if ($temp_num == $temp_count) {
                        if ($key == 'id') {
                            $temp_str .= "'" . $key . "' => " . ($value ?? null) . "";
                        } else {
                            $temp_str .= "'" . $key . "' => '" . ($value ?? null) . "'";
                        }
                    } else {
                        if ($key == 'id') {
                            $temp_str .= "'" . $key . "' => " . ($value ?? null) . ", ";
                        } else {
                            $temp_str .= "'" . $key . "' => '" . ($value ?? null) . "', ";
                        }
                    }
                }

            }
            $temp_str .= '],' . "\r\n";
        }
        return $temp_str;
    }

    //For retrieving permissions
    public function getPermission(Request $request)
    {
        $permissions = [];
        if ($request->role) {
            $permissions = Role::findByName($request->role)->permissions;
        }

        $items = Collection::make(Permission::get());
        $paginates = new LengthAwarePaginator(collect($items)->forPage($request->page, 10)->values(), $items->count(), 10, $request->page, []);

        return ['data' => $paginates, 'all' => Permission::get(), 'selected' => $permissions];
    }

    //For retrieving roles list
    public function getRoles(Request $request)
    {
        $roles = [];

        if ($request->user) {
            foreach (User::with("roles")->where("id", $request->user)->get() as $key => $value) {
                foreach ($value->roles as $key1 => $value1) {
                    $temp = [];
                    $temp['id'] = $value1->id;
                    $temp['name'] = $value1->name;
                    $temp['description'] = $value1->description;
                    array_push($roles, $temp);
                }
            }
        }
        $items = Collection::make(Role::get());
        $paginates = new LengthAwarePaginator(collect($items)->forPage($request->page, 10)->values(), $items->count(), 10, $request->page, []);

        return ['data' => $paginates, 'selected' => $roles];
    }

    //For retrieving user roles
    public function getUserRole(Request $request)
    {
        $items = Collection::make(User::with(["roles"])->get());
        return new LengthAwarePaginator(collect($items)->forPage($request->page, 5)->values(), $items->count(), 5, $request->page, []);
    }

    //For saving roles
    public function storeRole(Request $request)
    {
        $data = Role::query();

        if ($data->where("id", $request->id)->count() > 0) {
            $data = Role::where("id", $request->id)
                ->update([
                    'name' => $request->name,
                    'description' => $request->description,
                ]);
        } else {
            if (Role::where('name', $request->name)->count() > 0) {
                return ['type' => 1];
            }
            $data = Role::create([
                'name' => $request->name,
                'description' => $request->description,
            ]);
        }
        return ['data' => $request->all(), 'type' => 0];
    }

    //For saving permissions
    public function storePermission(Request $request)
    {
        $data = Permission::query();

        if ($data->where("id", $request->id)->count() > 0) {
            $data = Permission::where("id", $request->id)
                ->update([
                    'name' => $request->name,
                    'description' => $request->description,
                ]);
        } else {
            if (Permission::where('name', $request->name)->count() > 0) {
                return ['type' => 1];
            }
            $data = Permission::create([
                'name' => $request->name,
                'description' => $request->description,
            ]);
        }
        return ['data' => $request->all(), 'type' => 0];
    }

    //For saving role permissions
    public function storeRolePermission(Request $request)
    {
        $get_permission = [];
        foreach ($request->selected as $key => $value) {
            array_push($get_permission, $value['name']);
        }
        $data = Role::where("name", $request->role)->first();
        $data->syncPermissions($get_permission);

        //Access rights

        foreach (User::with("roles")->get() as $key => $value) {
            $get_roles = [];
            foreach ($value->roles as $key1 => $value1) {
                array_push($get_roles, $value1->name);
            }
            $user = User::where("id", $value->id)->first();
            $user->syncRoles($get_roles);
            $get_permission = User::where("id", $value->id)->first()->getPermissionsViaRoles($get_roles);
            $user->syncPermissions($get_permission);
        }
        return true;
    }

    //For saving user roles
    public function storeUserRole(Request $request)
    {
        //Get the names of role from the list
        $get_role = [];
        foreach ($request->selected as $key => $value) {
            array_push($get_role, $value['name']);
        }

        //Get selected user
        $user = User::where("id", $request->user)->first();
        //Syncronize roles (add/delete)
        $user->syncRoles($get_role);

        //Get current permission based on role/user selected
        $get_permission = $user->getPermissionsViaRoles($get_role);
        //Syncornize permission (add/delete)
        return $user->syncPermissions($get_permission);
    }

    //For removing user roles
    public function removeUserRole(Request $request)
    {
        $user = User::where("id", 2)->first();
        return $user->removeRole('Admin');
    }
}
