<?php

namespace Exceptions\Runtime;

    class RuntimeException extends \RuntimeException {}

        // Entities
        class DetachedEntityInstanceException extends RuntimeException {}

        class InvalidMemberTypeException extends RuntimeException {}

        // Users
        class DuplicateEntryException extends RuntimeException {}
    
            class DuplicateEmailException extends DuplicateEntryException {}
    
            class DuplicateUsernameException extends DuplicateEntryException {}
            
        class DatabaseUserInsertException extends RuntimeException {}

        class UserNotFoundException extends RuntimeException {}

        class UserAlreadyExistsException extends RuntimeException {}

        class InvitationNotFoundException extends RuntimeException {}

        class InvitationAlreadyExistsException extends RuntimeException {}

        class TokenValidityException extends RuntimeException {}

        class TokenNotFoundException extends TokenValidityException {}

        class TokenValidityExpiredException extends TokenValidityException {}

        class InvalidUserInvitationEmailException extends RuntimeException {}
        
        class InvalidStateException extends RuntimeException {}

        // Listings
        class ListingNotFoundException extends RuntimeException {}
        
        class ListingItemNotFoundException extends RuntimeException {}

        class ListingItemDayAlreadyExistsException extends RuntimeException {}

        class LocalityNotFoundException extends RuntimeException {}
        
        class WorkedHoursNotFoundException extends RuntimeException {}
        
        class DuplicateItemInCollectionException extends RuntimeException {}

        class NegativeResultOfTimeCalcException extends RuntimeException {}

		class InvalidTimeMemberTypeException extends RuntimeException {}

        class DayExceedCurrentMonthException extends RuntimeException {}

        class ListingAlreadyContainsListingItemException extends RuntimeException {}

        class ShiftEndBeforeStartException extends RuntimeException {}

        class ListingPreviewNotFoundException extends RuntimeException {}

        class CollisionItemsSelectionException extends RuntimeException {}

        class NoCollisionListingItemSelectedException extends RuntimeException {}

        // Messages
        class MessageLengthException extends RuntimeException {}

        class MessageNotFoundException extends RuntimeException {}

        // WorkedHours
        class OtherHoursZeroTimeException extends RuntimeException {}