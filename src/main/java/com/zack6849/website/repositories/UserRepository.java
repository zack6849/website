package com.zack6849.website.repositories;

import com.zack6849.website.domain.User;
import org.springframework.data.repository.CrudRepository;

public interface UserRepository extends CrudRepository<User, Integer> {

    User findByUsername(String username);

    User findByEmailaddress(String email);

}
