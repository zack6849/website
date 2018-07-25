package com.zack6849.website.repositories;

import com.zack6849.website.domain.Role;
import org.springframework.data.repository.CrudRepository;

public interface RoleRepository extends CrudRepository<Role, Integer> {
    Role findOneByName(String name);
}
