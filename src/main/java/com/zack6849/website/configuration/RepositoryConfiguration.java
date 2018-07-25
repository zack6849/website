package com.zack6849.website.configuration;

import org.springframework.boot.autoconfigure.EnableAutoConfiguration;
import org.springframework.boot.autoconfigure.domain.EntityScan;
import org.springframework.context.annotation.Configuration;
import org.springframework.data.jpa.repository.config.EnableJpaRepositories;
import org.springframework.transaction.annotation.EnableTransactionManagement;

@Configuration
@EnableAutoConfiguration
@EntityScan(basePackages =  {"com.zack6849.website.domain"})
@EnableJpaRepositories(basePackages = {"com.zack6849.website.repositories"})
@EnableTransactionManagement
public class RepositoryConfiguration {

}
