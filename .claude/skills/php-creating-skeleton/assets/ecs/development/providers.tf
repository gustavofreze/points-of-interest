terraform {
  required_version = ">= 1.9.0"

  required_providers {
    aws = {
      source  = "hashicorp/aws"
      version = "~> 6.40"
    }
  }

  backend "s3" {
    key    = "services/<service-name>/<cloud-region>/development/<service-name>.tfstate"
    region = "<cloud-region>"
  }
}

provider "aws" {
  region = var.aws_region

  default_tags {
    tags = {
      Project     = var.prefix
      Service     = local.service
      ManagedBy   = "terraform"
      Environment = local.environment
    }
  }
}
