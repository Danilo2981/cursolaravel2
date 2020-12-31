<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use App\Models\User;
use App\Models\Skill;
use App\Models\Profession;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CreateUsersTest extends TestCase
{
    use RefreshDatabase;

    protected $defaultData = [
        'name' => 'Danilo',
        'email' => 'danilo.vega@gmail.com',
        'password' => '123456',
        'profession_id' => '',
        'bio' => 'Programador de Laravel y Vue.js',
        'twitter' => 'https://twitter.com/danilo',
        'role' => 'user'
    ];

    /** @test */

    function it_loads_the_new_users_page() 
    {
        $profession = Profession::factory()->create();

        $skillA = Skill::factory()->create();
        $skillB = Skill::factory()->create();

        $this->get('/usuarios/crear')
            ->assertStatus(200)
            ->assertSee('Crear nuevo usuario')
            ->assertViewHas('professions', function($professions) use ($profession){
                return $professions->contains($profession);
            })
            ->assertViewHas('skills', function($skills) use ($skillA, $skillB){
                return $skills->contains($skillA) && $skills->contains($skillB);
            });
    }

    /** @test */

    function it_creates_a_new_user()
    {
        $profession = Profession::factory()->create();

        $skillA = Skill::factory()->create();
        $skillB = Skill::factory()->create();
        $skillC = Skill::factory()->create();

        $this->post('/usuarios/', $this->withData([
            'skills' => [$skillA->id, $skillB->id],
            'profession_id' => $profession->id
        ]))->assertRedirect('usuarios');

        $this->assertCredentials([
            'name' => 'Danilo',
            'email' => 'danilo.vega@gmail.com',
            'password' => '123456',
            'role' => 'user'
        ]);
        
        $user = User::findByEmail('danilo.vega@gmail.com');    

        $this->assertDatabaseHas('user_profiles', [
            'bio' => 'Programador de Laravel y Vue.js',
            'twitter' => 'https://twitter.com/danilo',
            'user_id' => $user->id,
            'profession_id' => $profession->id
        ]);

        $this->assertDatabaseHas('user_skill', [
            'user_id' => $user->id,
            'skill_id' => $skillA->id
        ]);

        $this->assertDatabaseHas('user_skill', [
            'user_id' => $user->id,
            'skill_id' => $skillB->id
        ]);

        $this->assertDatabaseMissing('user_skill', [
            'user_id' => $user->id,
            'skill_id' => $skillC->id
        ]);
    }

    /** @test */

    function the_twitter_field_is_optional()
    {
        $this->post('/usuarios/', $this->withData([
            'twitter' => null
        ]))->assertRedirect('usuarios');

        $this->assertCredentials([
            'name' => 'Danilo',
            'email' => 'danilo.vega@gmail.com',
            'password' => '123456'
        ]);
        
        $this->assertDatabaseHas('user_profiles', [
            'bio' => 'Programador de Laravel y Vue.js',
            'twitter' => null,
            'user_id' => User::findByEmail('danilo.vega@gmail.com')->id
        ]);
    }

    /** @test */

    function the_role_field_is_optional()
    {
        $this->post('/usuarios/', $this->withData([
            'role' => null
        ]))->assertRedirect('usuarios');

        $this->assertDatabaseHas('users', [
            'email' => 'danilo.vega@gmail.com',
            'role' => 'user'
        ]);
    }

    /** @test */

    function the_role_must_be_valid()
    {
        $this->handleValidationExceptions();

        $this->post('/usuarios/', $this->withData([
            'role' => 'invalid-role'
        ]))->assertSessionHasErrors('role');

        $this->assertDatabaseMissing('users', [
            'email' => 'danilo.vega@gmail.com',
        ]);
    }

     /** @test */

     function the_profession_field_is_optional()
     {
         $this->post('/usuarios/', $this->withData([
             'profession_id' => ''
         ]))->assertRedirect('usuarios');
 
         $this->assertCredentials([
             'name' => 'Danilo',
             'email' => 'danilo.vega@gmail.com',
             'password' => '123456',
         ]);
         
         $this->assertDatabaseHas('user_profiles', [
             'bio' => 'Programador de Laravel y Vue.js',
             'user_id' => User::findByEmail('danilo.vega@gmail.com')->id,
             'profession_id' => null,
         ]);
     }

    /** @test */

    function the_name_is_required()
    {   
        $this->handleValidationExceptions();

        $this->from('usuarios/nuevo')
        ->post('/usuarios/', $this->withData([
            'name' => ''
        ]))
        ->assertRedirect('usuarios/nuevo')
        ->assertSessionHasErrors(['name' => 'El campo es obligatorio']);

        $this->assertEquals(0, User::count());      
    }

    /** @test */

    function the_user_is_redirected_to_the_previos_page_when_the_validation_fail()
    {   
        $this->handleValidationExceptions();

        $this->from('usuarios/nuevo')
        ->post('/usuarios/', [])
        ->assertRedirect('usuarios/nuevo');

        $this->assertEquals(0, User::count());      
    }

    /** @test */

    function the_email_is_required()
    {   
        $this->handleValidationExceptions();

        $this->post('/usuarios/', $this->withData([
            'email' => '',
        ]))->assertSessionHasErrors(['email']);

        $this->assertEquals(0, User::count());      
    }

    /** @test */

    function the_email_must_be_valid()
    {   
        $this->handleValidationExceptions();

        $this->post('/usuarios/', $this->withData([
            'email' => 'correo-no-valido',
        ]))->assertSessionHasErrors(['email']);

        $this->assertEquals(0, User::count());      
    }

    /** @test */

    function the_email_must_be_unique()
    {   
        $this->handleValidationExceptions();
        
        $user = User::factory()->create([
            'email' => 'danilo.vega@gmail.com'
        ]);

        $this->post('/usuarios/', $this->withData([
            'email' => 'danilo.vega@gmail.com',
        ]))->assertSessionHasErrors(['email']);

        $this->assertEquals(1, User::count());      
    }

    /** @test */

    function the_password_is_required()
    {   
        $this->handleValidationExceptions();

        $this->post('/usuarios/', $this->withData( [
            'password' => ''
        ]))->assertSessionHasErrors(['password']);

        $this->assertEquals(0, User::count());      
    }

     /** @test */

     function the_profession_must_be_valid()
     {   
        $this->handleValidationExceptions();

         $this->post('/usuarios/', $this->withData([
             'profession_id' => '999'
         ]))->assertSessionHasErrors(['profession_id']);
 
         $this->assertEquals(0, User::count());      
     }

     /** @test */

     function only_selectable_professions_are_valid()
     {   
        $deletedProfession = Profession::factory()->create([
            'deleted_at' => now()->format('Y-m-d'),
        ]);
        
        $this->handleValidationExceptions();

         $this->post('/usuarios/', $this->withData([
             'profession_id' => $deletedProfession->id
         ]))->assertSessionHasErrors(['profession_id']);
 
         $this->assertEquals(0, User::count());      
     }

     /** @test */

     function the_skills_must_be_an_array()
     {   
        $this->handleValidationExceptions();

         $this->post('/usuarios/', $this->withData([
             'skills' => 'PHP, JS'
         ]))->assertSessionHasErrors(['skills']);
 
         $this->assertEquals(0, User::count());      
     }

     /** @test */

     function the_skills_must_be_valid()
     {   
        $this->handleValidationExceptions();

        $skillA = Skill::factory()->create();
        $skillB = Skill::factory()->create();

         $this->post('/usuarios/', $this->withData([
             'skills' => [$skillA->id, $skillB->id+1]
         ]))->assertSessionHasErrors(['skills']);
 
         $this->assertEquals(0, User::count());      
     }

    /** @test */

    function the_password_is_min()
    {   
        $this->handleValidationExceptions();

        $this->post('/usuarios/', $this->withData([
            'password' => '123'
        ]))->assertSessionHasErrors(['password']);

        $this->assertEquals(0, User::count());      
    }

}
